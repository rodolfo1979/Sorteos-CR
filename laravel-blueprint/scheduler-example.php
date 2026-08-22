<?php

// En routes/console.php o app/Console/Kernel.php segun version de Laravel.

use Illuminate\Support\Facades\Schedule;

Schedule::command('raffles:expire-reservations')->everyMinute()->withoutOverlapping();
