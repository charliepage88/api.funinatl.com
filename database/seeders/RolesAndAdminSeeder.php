<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Create admin user
        $admin = \App\User::firstOrCreate(
            ['email' => 'me@charlespage.me'],
            [
                'name'              => 'Charlie Page',
                'email_verified_at' => now(),
                'password'          => bcrypt('fusion'),
            ]
        );

        // Assign admin role
        $admin->assignRole($adminRole);
    }
}
