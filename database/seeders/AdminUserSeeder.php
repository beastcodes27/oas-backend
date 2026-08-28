<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the administrator and admission officer accounts.
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
                'is_admissions' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'officer@shuleyetu.ac.tz'],
            [
                'name' => 'Admissions Officer',
                'phone' => '+255 700 000 002',
                'password' => Hash::make(config('app.officer_default_password', 'Officer@12345')),
                'is_admin' => false,
                'is_admissions' => true,
            ],
        );
    }
}
