<?php

namespace Tests\Unit;

use App\Jobs\HandlePornstarFeedItem;
use App\Services\ImagePublisher;
use App\Services\PornstarPublisher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HandlePornstarFeedItemTest extends TestCase
{
    public function test_it_publishes_pornstar_and_new_image_urls(): void
    {
        // Arrange
        $item = [
            'id' => 99,
            'name' => 'Test Star',
            'thumbnails' => [
                [
                    'type' => 'pc',
                    'width' => 123,
                    'height' => 456,
                    'urls' => [
                        'https://example.com/image.jpg',
                    ],
                ],
            ],
        ];

        $hash = md5('https://example.com/image.jpg');
        $cacheKey = "seen_image_url:$hash";

        // Fake Cache behavior
        Cache::shouldReceive('has')->with($cacheKey)->andReturn(false);
        Cache::shouldReceive('put')->with(
            $cacheKey,
            true,
            \Mockery::type(\Illuminate\Support\Carbon::class)
        );

        // Mock PornstarPublisher to expect publish with full $item
        $this->mock(PornstarPublisher::class, function ($mock) use ($item) {
            $mock->shouldReceive('publish')
                ->once()
                ->with($item);
        });

        // Mock ImagePublisher to expect specific payload
        $this->mock(ImagePublisher::class, function ($mock) use ($item) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(\Mockery::on(function ($payload) use ($item) {
                    return $payload['pornstar_id'] === $item['id']
                        && $payload['url'] === 'https://example.com/image.jpg'
                        && $payload['type'] === 'pc';
                }));
        });

        // Act
        $job = new HandlePornstarFeedItem($item);
        $job->handle();
    }
}
