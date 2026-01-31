<?php

namespace App\Console\Commands\RayDemo;

use App\Domain\User\Models\User;
use Illuminate\Console\Command;

class PauseCommand extends Command
{
    protected $signature = 'ray:pause';

    public function handle()
    {
        ray()->clearAll();

        $this->info('Click "Continue" in Ray to step through each user.');
        $this->newLine();

        $users = User::limit(3)->get();

        foreach ($users as $user) {
            ray($user->email);
            ray()->pause();
        }

        ray()->confetti();
    }
}
