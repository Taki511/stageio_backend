<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\Recruiter;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RecruiterSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get Recruiter User
        $recruiterUser = User::firstOrCreate(
            ['email' => 'recruiter@stageio.com'],
            [
                'name' => 'Recruiter User',
                'password' => Hash::make('recruiter123'),
                'role' => User::ROLE_RECRUITER,
            ]
        );

        // Create or get Recruiter Profile
        Recruiter::firstOrCreate(
            ['user_id' => $recruiterUser->id],
            [
                'first_name' => 'Recruiter',
                'last_name' => 'User',
                'company_email' => 'recruiter@company.com',
            ]
        );

        // Create or get Company Profile for the Recruiter
        // Note: recruiter_id references users.id, not recruiters.id
        CompanyProfile::firstOrCreate(
            ['recruiter_id' => $recruiterUser->id],
            [
                'name' => 'Tech Company Algeria',
                'description' => 'A leading technology company in Algeria offering internship opportunities for students.',
                'wilaya' => 'Constantine',
                'address' => '123 Tech Street, Constantine, Algeria',
                'logo' => null,
            ]
        );

        $this->command->info('Recruiter user created/updated successfully!');
        $this->command->info('Email: recruiter@stageio.com');
        $this->command->info('Password: recruiter123');
    }
}
