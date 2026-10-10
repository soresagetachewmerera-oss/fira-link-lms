<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'email' => 'admin@eduex.com',
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'role' => 'admin',
                'password' => 'password',
                'is_approved' => true,
                'spatie_role' => 'admin',
                'meta' => [],
            ],
            [
                'email' => 'instructor@eduex.com',
                'name' => 'Demo Instructor',
                'email_verified_at' => now(),
                'role' => 'instructor',
                'password' => 'password',
                'is_approved' => true,
                'spatie_role' => 'instructor',
                'meta' => [
                    'bio' => 'Passionate instructor ready to share knowledge.',
                ],
            ],
            [
                'email' => 'student@eduex.com',
                'name' => 'Demo Student',
                'email_verified_at' => now(),
                'role' => 'student',
                'password' => 'password',
                'is_approved' => true,
                'spatie_role' => 'student',
                'meta' => [],
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(
                Arr::only($account, ['email']),
                [
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'role' => $account['role'],
                    'is_approved' => $account['is_approved'],
                    'email_verified_at' => $account['email_verified_at'],
                    ...$account['meta'],
                ]
            );

            $spatieRole = Role::where('name', $account['spatie_role'])->first();
            if ($spatieRole && !$user->hasRole($spatieRole->name)) {
                $user->assignRole($spatieRole);
            }

            $this->command->info(sprintf(
                '%s user ensured: %s / %s',
                ucfirst($account['role']),
                $account['email'],
                $account['password']
            ));
        }
    }
}

