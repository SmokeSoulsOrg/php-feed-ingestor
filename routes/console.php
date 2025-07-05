<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('ingest-pornstar-feed')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
