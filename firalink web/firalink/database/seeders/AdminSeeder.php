<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
                'email' => 'admin@fira-link.com',
                'name' => 'Fira Link Admin',
                'email_verified_at' => now(),
                'role' => 'admin',
                'password' => 'password',
                'is_approved' => true,
                'spatie_role' => 'admin',
                'meta' => [],
            ],
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

        // Known demo passwords only on a developer machine or in tests (gap analysis GAP-019).
        // On a server: no eduex demo accounts, and the platform admin gets a random password shown once.
        $isDevMachine = app()->environment('local', 'testing');
        if (!$isDevMachine) {
            $accounts = array_values(array_filter($accounts, fn ($a) => $a['email'] === 'admin@fira-link.com'));
            foreach ($accounts as $i => $a) {
                $accounts[$i]['password'] = Str::password(24);
            }
        }

        foreach ($accounts as $account) {
            $existed = User::where('email', $account['email'])->exists();
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

            if ($existed) {
                $this->command?->info(sprintf('%s user already exists: %s (password unchanged)', ucfirst($account['role']), $account['email']));
            } elseif ($isDevMachine) {
                $this->command?->info(sprintf('%s user ensured: %s / %s', ucfirst($account['role']), $account['email'], $account['password']));
            } else {
                $this->command?->warn(sprintf('%s user created: %s — one-time password: %s (save it now; it is not stored anywhere else)', ucfirst($account['role']), $account['email'], $account['password']));
            }
        }
    }
}

