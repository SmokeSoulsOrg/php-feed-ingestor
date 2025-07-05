<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Jobs\HandlePornstarFeedItem;
use Tests\TestCase;

class IngestPornstarFeedTest extends TestCase
{
    public function test_it_fetches_feed_and_dispatches_jobs(): void
    {
        // Arrange
        $mockItems = [
            ['id' => 1, 'name' => 'Test Star A', 'thumbnails' => []],
            ['id' => 2, 'name' => 'Test Star B', 'thumbnails' => []],
        ];

        // Mock HTTP feed response
        Http::fake([
            '*' => Http::response([
                'items' => $mockItems
            ]),
        ]);

        // Fake job dispatch
        Queue::fake();

        // Mock feed URL
        config(['services.feed.pornstars_url' => 'https://fake-url.test/feed.json']);

        // Act
        $this->artisan('ingest-pornstar-feed')
            ->expectsOutput('Fetching feed from: https://fake-url.test/feed.json')
            ->expectsOutput('Found 2 items.')
            ->expectsOutput('✅ All items dispatched to queue.')
            ->assertExitCode(0);

        // Assert: jobs were dispatched
        Queue::assertPushed(HandlePornstarFeedItem::class, 2);
    }

    public function test_it_logs_error_for_invalid_json(): void
    {
        Http::fake([
            '*' => Http::response('{"invalid": "json"', 200),
        ]);

        config(['services.feed.pornstars_url' => 'https://fake-broken.test']);

        Log::spy(); // enable the spy first

        $this->artisan('ingest-pornstar-feed')
            ->expectsOutput('Fetching feed from: https://fake-broken.test')
            ->expectsOutput('❌ Feed returned unexpected format or empty data.')
            ->assertExitCode(1);

        // Then get the spy and assert log call
        Log::getFacadeRoot()
            ->shouldHaveReceived('error')
            ->with('[Feed Error] JSON decoded but format is invalid or empty', \Mockery::type('array'))
            ->once();
    }
}
