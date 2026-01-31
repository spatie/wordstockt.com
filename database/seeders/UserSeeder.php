<?php

namespace Database\Seeders;

use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'username' => 'freek',
            'email' => 'freek@spatie.be',
            'is_admin' => true,
        ]);

        User::factory()->create([
            'username' => 'jessica',
            'email' => 'jessica@spatie.be',
        ]);

        User::factory()->create([
            'username' => 'marvin',
            'email' => 'marvin@spatie.be',
        ]);
    }
}
