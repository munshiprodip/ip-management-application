<?php

namespace Database\Seeders;

use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Initial Admin / Operator Accounts for Local / Testing Baseline
        $password = Hash::make('Password123!');

        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@kyamch.local',
                'password' => $password,
                'role' => UserRole::SUPER_ADMIN,
                'status' => 'active',
                'department' => 'IT Infrastructure & Security',
                'timezone' => 'Asia/Dhaka',
            ],
            [
                'name' => 'Network Administrator',
                'email' => 'netadmin@kyamch.local',
                'password' => $password,
                'role' => UserRole::NETWORK_ADMIN,
                'status' => 'active',
                'department' => 'Network Operations',
                'timezone' => 'Asia/Dhaka',
            ],
            [
                'name' => 'IP Operations Lead',
                'email' => 'operator@kyamch.local',
                'password' => $password,
                'role' => UserRole::OPERATOR,
                'status' => 'active',
                'department' => 'Systems & Helpdesk',
                'timezone' => 'Asia/Dhaka',
            ],
            [
                'name' => 'Compliance Auditor',
                'email' => 'auditor@kyamch.local',
                'password' => $password,
                'role' => UserRole::AUDITOR,
                'status' => 'active',
                'department' => 'IT Governance & Audit',
                'timezone' => 'Asia/Dhaka',
            ],
            [
                'name' => 'Infrastructure Viewer',
                'email' => 'viewer@kyamch.local',
                'password' => $password,
                'role' => UserRole::VIEWER,
                'status' => 'active',
                'department' => 'Clinical Engineering',
                'timezone' => 'Asia/Dhaka',
            ],
            [
                'name' => 'Change Approver',
                'email' => 'approver@kyamch.local',
                'password' => $password,
                'role' => UserRole::APPROVER,
                'status' => 'active',
                'department' => 'Network Architecture Board',
                'timezone' => 'Asia/Dhaka',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            AuditEvent::firstOrCreate(
                [
                    'event_type' => 'user.seeded',
                    'subject_id' => $user->id,
                ],
                [
                    'actor_name' => 'System Seeder',
                    'subject_type' => User::class,
                    'subject_label' => $user->name . ' (' . $user->email . ')',
                    'reason' => 'Initial baseline seed for role: ' . $user->role->value,
                    'created_at' => now(),
                ]
            );
        }

        // Call Stage 1, Stage 2, Stage 3, Stage 4, Stage 5, Stage 6, Stage 7 & Stage 8 Seeders
        $this->call([
            Stage1Seeder::class,
            Stage2Seeder::class,
            Stage3Seeder::class,
            Stage4Seeder::class,
            Stage5Seeder::class,
            Stage6Seeder::class,
            Stage7Seeder::class,
            Stage8Seeder::class,
        ]);
    }
}
