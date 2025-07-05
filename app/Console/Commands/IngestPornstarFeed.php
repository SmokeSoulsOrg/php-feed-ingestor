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
     *
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

        // Detect and warn on known invalid \x escape sequences
        if (preg_match('/\\\\x[0-9A-Fa-f]{2}/', $rawJson, $match)) {
            Log::warning('[Feed Warning] Invalid \\x escape sequence in feed', [
                'example' => $match[0],
            ]);
        }

        // Sanitize: remove invalid \xNN sequences (not valid JSON)
        $cleaned = preg_replace('/\\\\x[0-9A-Fa-f]{2}/', '', $rawJson);

        // Attempt to decode sanitized JSON
        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::error('[Feed Error] Failed to decode sanitized JSON', [
                'json_error' => json_last_error_msg(),
                'raw' => substr($cleaned, 0, 500),
            ]);
            $this->error("❌ Feed could not be decoded correctly.");
            return CommandAlias::FAILURE;
        }

        if (!isset($data['items']) || !is_array($data['items'])) {
            Log::error('[Feed Error] JSON decoded but format is invalid or missing items array', [
                'raw' => substr($cleaned, 0, 500),
            ]);
            $this->error("❌ Feed returned unexpected format.");
            return CommandAlias::FAILURE;
        }

        $items = $data['items'];
        $this->info("Found " . count($items) . " items.");

        foreach ($items as $item) {
            dispatch((new HandlePornstarFeedItem($item))->onQueue('ingest-items'));
        }

        $this->info("✅ All items dispatched to queue.");
        return CommandAlias::SUCCESS;
    }
}
