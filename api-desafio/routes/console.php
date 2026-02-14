<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('events:publish-pending')->everyTenSeconds();

