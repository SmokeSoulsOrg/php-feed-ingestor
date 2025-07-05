<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Jobs\HandlePornstarFeedItem;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class IngestPornstarFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingest-pornstar-feed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch pornstar feed and dispatch jobs to process each item';

    /**
     * Execute the console command.
     * @throws ConnectionException
     */
    public function handle(): int
    {
        $url = config('services.feed.pornstars_url');
        $this->info("Fetching feed from: {$url}");

        $response = Http::get($url);

        if (!$response->successful()) {
            $this->error("❌ Failed to fetch feed: HTTP {$response->status()}");
            return CommandAlias::FAILURE;
        }

        $rawJson = $response->body();

        // Log the raw body snippet for debugging
        Log::debug('[Feed Debug] Raw response snippet', [
            'body' => substr($rawJson, 0, 500),
        ]);

        // Detect and warn on known escape issues
        if (preg_match('/\\\\(x|[^"\/bfnrtu0-9])/', $rawJson, $match)) {
            Log::warning('[Feed Warning] Invalid escape sequence in feed', [
                'example' => $match[0],
            ]);
        }

        // Sanitize escape sequences: remove \x and other non-standard escapes
        $sanitized = preg_replace('/\\\\(x|[^"\/bfnrtu0-9])/', '', $rawJson);

        // Attempt to parse JSON
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

        foreach ($items as $item) {
            dispatch(new HandlePornstarFeedItem($item));
        }

        $this->info("✅ All items dispatched to queue.");
        return CommandAlias::SUCCESS;
    }

}
