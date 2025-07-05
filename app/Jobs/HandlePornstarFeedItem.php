<?php

namespace App\Jobs;

use App\Services\ImagePublisher;
use App\Services\PornstarPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

class HandlePornstarFeedItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $item;

    public function __construct(array $item)
    {
        $this->item = $item;
    }

    public function handle(): void
    {
        $pornstar = $this->item;
        $id = $pornstar['id'] ?? null;
        $name = $pornstar['name'] ?? 'unknown';

        // 1. Publish pornstar metadata to RabbitMQ
        try {
            app(PornstarPublisher::class)->publish($pornstar);
            Log::info("[HandlePornstarFeedItem] Published pornstar {$name} to 'pornstar-events'");
        } catch (\Throwable $e) {
            Log::error('[HandlePornstarFeedItem] Failed to publish pornstar metadata', [
                'error' => $e->getMessage(),
                'pornstar_id' => $id,
            ]);
        }

        // 2. Deduplicate and publish image download jobs
        foreach ($pornstar['thumbnails'] ?? [] as $thumb) {
            foreach ($thumb['urls'] ?? [] as $url) {
                $hash = md5($url);
                $cacheKey = "seen_image_url:$hash";

                if (!Cache::has($cacheKey)) {
                    $ttlDays = (int) config('services.image_cache_ttl_days', 7);
                    Cache::put($cacheKey, true, now()->addDays($ttlDays));

                    $payload = [
                        'pornstar_id' => $id,
                        'name' => $name,
                        'url' => $url,
                        'type' => $thumb['type'] ?? null,
                        'width' => $thumb['width'] ?? null,
                        'height' => $thumb['height'] ?? null,
                    ];

                    try {
                        app(ImagePublisher::class)->publish($payload);
                        Log::info("[HandlePornstarFeedItem] Published image job for URL: {$url}");
                    } catch (\Throwable $e) {
                        Log::error('[HandlePornstarFeedItem] Failed to publish image job', [
                            'error' => $e->getMessage(),
                            'url' => $url,
                            'payload' => $payload,
                        ]);
                    }
                }
            }
        }
    }
}
