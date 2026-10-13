<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Permissions
        $permissions = [
            'view products',
            'manage products',
            'export data',
            'manage users',
            'manage roles',
            'view audit logs',
            'send chat',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 2. Create Roles and Assign Permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(['view products', 'manage products', 'export data', 'view audit logs', 'send chat']);

        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        $staffRole->syncPermissions(['view products', 'export data', 'send chat']);

        // 3. Assign Role to Existing Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@hybrid.local'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles([$superAdminRole]);

        // 4. Create Demo Staff User
        $staff = User::firstOrCreate(
            ['email' => 'staff@hybrid.local'],
            [
                'name' => 'Demo Staff',
                'password' => Hash::make('password'),
            ]
        );
        $staff->syncRoles([$staffRole]);
    }
}
