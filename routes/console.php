<?php

declare(strict_types=1);

use App\Jobs\ProcessColaJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ProcessColaJob())->everyFiveMinutes()->withoutOverlapping();
