<?php

namespace Database\Seeders;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@stageio.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        // Create or get Admin Profile
        $admin = Administrator::firstOrCreate(
            ['user_id' => $adminUser->id],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'university_email' => 'admin@univ-constantine2.com',
            ]
        );

        // Ensure this first admin is always the super admin
        $admin->update(['is_super_admin' => true]);

        $this->command->info('Admin user created/updated successfully!');
        $this->command->info('Email: admin@stageio.com');
        $this->command->info('Password: admin123');
        $this->command->info('Role: SUPER ADMIN');
    }
}
