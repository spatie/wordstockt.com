<?php

namespace App\Console\Commands\RayDemo;

use App\Domain\User\Actions\Auth\SendPasswordResetEmailAction;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;

class MailCommand extends Command
{
    protected $signature = 'ray:mail';

    public function handle(SendPasswordResetEmailAction $sendPasswordResetEmailAction): void
    {
        ray()->clearAll();

        $this->info('Sending ResetPasswordMail...');

        $this->newLine();

        // ray()->showQueries();

        $user = User::first();

        $sendPasswordResetEmailAction->execute($user);

        ray('Password reset mail sent');
    }
}
