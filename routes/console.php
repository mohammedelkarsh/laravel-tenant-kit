<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('usage:snapshot-gauges')->daily()->when(
    fn (): bool => (bool) config('usage.enabled'),
);
