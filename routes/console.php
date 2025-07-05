<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('ingest-pornstar-feed')
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/scheduler.log'));
