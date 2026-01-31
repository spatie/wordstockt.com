<?php

namespace App\Console\Commands\RayDemo;

use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

class PerformanceCommand extends Command
{
    protected $signature = 'ray:performance';

    public function handle()
    {
        ray()->clearAll();

        ray()->measure();

        Sleep::for(750)->milliseconds();
        ray()->measure();

        Sleep::for(1250)->milliseconds();
        ray()->measure();

    }
}
