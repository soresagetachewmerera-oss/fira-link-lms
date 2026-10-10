<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Course permissions
            'create-course',
            'edit-course',
            'delete-course',
            'view-course',
            
            // Enrollment permissions
            'enroll-course',
            'unenroll-course',
            
            // Instructor permissions
            'approve-instructor',
            'reject-instructor',
            
            // User permissions
            'view-users',
            'delete-users',
            
            // Admin permissions
            'manage-all',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $instructorRole = Role::firstOrCreate(['name' => 'instructor']);
        $studentRole = Role::firstOrCreate(['name' => 'student']);

        // Assign permissions to admin (all permissions)
        $adminRole->givePermissionTo(Permission::all());

        // Assign permissions to instructor
        $instructorRole->givePermissionTo([
            'create-course',
            'edit-course',
            'delete-course',
            'view-course',
        ]);

        // Assign permissions to student
        $studentRole->givePermissionTo([
            'view-course',
            'enroll-course',
            'unenroll-course',
        ]);
    }
}

