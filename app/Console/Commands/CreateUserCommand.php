<?php

namespace App\Console\Commands;

use App\Domain\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Create a new verified user';

    public function handle(): int
    {
        $username = text(
            label: 'Username',
            required: true,
            validate: fn (string $value) => $this->validateUsername($value),
        );

        $email = text(
            label: 'Email',
            required: true,
            validate: fn (string $value) => $this->validateEmail($value),
        );

        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? 'Password must be at least 8 characters.' : null,
        );

        User::create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $this->info("User '{$username}' created and verified successfully.");

        return self::SUCCESS;
    }

    private function validateUsername(string $value): ?string
    {
        $validator = Validator::make(
            ['username' => $value],
            ['username' => 'required|string|min:3|max:20|unique:users,username']
        );

        if ($validator->fails()) {
            return $validator->errors()->first('username');
        }

        return null;
    }

    private function validateEmail(string $value): ?string
    {
        $validator = Validator::make(
            ['email' => $value],
            ['email' => 'required|email|unique:users,email']
        );

        if ($validator->fails()) {
            return $validator->errors()->first('email');
        }

        return null;
    }
}
