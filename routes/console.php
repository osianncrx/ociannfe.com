<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('fe:procesar-cola --timeout=55')->everyMinute()->withoutOverlapping();
Schedule::command('fe:consultar-estados')->everyMinute()->withoutOverlapping();
