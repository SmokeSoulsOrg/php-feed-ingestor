<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('ingest-pornstar-feed')
//    ->dailyAt('02:00') // this would be a normal value
    ->everyMinute() // for testing purposes during the interview task
    ->appendOutputTo(storage_path('logs/scheduler.log'));
