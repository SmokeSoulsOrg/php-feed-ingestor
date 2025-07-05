<?php

namespace App\Console\Commands;

use App\Jobs\HandlePornstarFeedItem;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class IngestPornstarFeed extends Command
{
    protected $signature = 'ingest-pornstar-feed';
    protected $description = 'Fetch pornstar feed and dispatch jobs to process each item';

    public function handle(): int
    {
        $url = config('services.feed.pornstars_url');
        $this->info("Fetching feed from: {$url}");

        try {
            $response = Http::timeout(30)->get($url);
        } catch (\Throwable $e) {
            $this->error("❌ HTTP request failed: {$e->getMessage()}");
            Log::error('[Feed Error] HTTP request failed', [
                'error' => $e->getMessage(),
            ]);
            return CommandAlias::FAILURE;
        }

        if (!$response->successful()) {
            $this->error("❌ Failed to fetch feed: HTTP {$response->status()}");
            return CommandAlias::FAILURE;
        }

        $rawJson = $response->body();

        Log::debug('[Feed Debug] Raw response snippet', [
            'body' => substr($rawJson, 0, 500),
        ]);

        if (preg_match('/\\\\(x|[^"\/bfnrtu0-9])/', $rawJson, $match)) {
            Log::warning('[Feed Warning] Invalid escape sequence in feed', [
                'example' => $match[0],
            ]);
        }

        $sanitized = preg_replace('/\\\\(x|[^"\/bfnrtu0-9])/', '', $rawJson);
        $data = json_decode($sanitized, true);

        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            Log::error('[Feed Error] JSON decoded but format is invalid or empty', [
                'json_error' => json_last_error_msg(),
                'raw' => substr($sanitized, 0, 500),
            ]);
            $this->error("❌ Feed returned unexpected format or empty data.");
            return CommandAlias::FAILURE;
        }

        $items = $data['items'];
        $this->info("Found " . count($items) . " items.");
        Log::info("[Feed Ingest] Dispatching jobs for " . count($items) . " items");

        try {
            foreach (array_chunk($items, 500) as $chunk) {
                foreach ($chunk as $item) {
                    dispatch(new HandlePornstarFeedItem($item));
                }
                usleep(100000); // 100ms delay to avoid queue flooding
            }
        } catch (\Throwable $e) {
            Log::error("[Feed Ingest] Dispatch error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("❌ Dispatch error: {$e->getMessage()}");
            return CommandAlias::FAILURE;
        }

        $this->info("✅ All items dispatched to queue.");
        return CommandAlias::SUCCESS;
    }
}
