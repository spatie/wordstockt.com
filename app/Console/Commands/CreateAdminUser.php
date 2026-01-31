<?php

namespace App\Console\Commands;

use App\Domain\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin';

    protected $description = 'Create a new admin user';

    public function handle(): int
    {
        $email = $this->askForEmail();
        if ($email === null) {
            return 1;
        }

        $username = $this->askForUsername();
        if ($username === null) {
            return 1;
        }

        $password = $this->askForPassword();
        if ($password === null) {
            return 1;
        }

        User::create([
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'is_admin' => true,
        ]);

        $this->info("Admin user '{$username}' created successfully.");

        return 0;
    }

    private function askForEmail(): ?string
    {
        $email = $this->ask('Email');

        $validator = Validator::make(
            ['email' => $email],
            ['email' => 'required|email|unique:users,email']
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return null;
        }

        return $email;
    }

    private function askForUsername(): ?string
    {
        $username = $this->ask('Username');

        $validator = Validator::make(
            ['username' => $username],
            ['username' => 'required|string|min:3|max:20|unique:users,username']
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('username'));

            return null;
        }

        return $username;
    }

    private function askForPassword(): ?string
    {
        $password = $this->secret('Password');

        $validator = Validator::make(
            ['password' => $password],
            ['password' => 'required|string|min:8']
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return null;
        }

        return $password;
    }
}
