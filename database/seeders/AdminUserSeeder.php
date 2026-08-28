<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the administrator account.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@shuleyetu.ac.tz'],
            [
                'name' => 'School Administrator',
                'phone' => '+255 700 000 001',
                'password' => Hash::make(config('app.admin_default_password', 'Admin@12345')),
                'is_admin' => true,
            ],
        );
    }
}
