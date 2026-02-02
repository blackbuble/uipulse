<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Design permissions
            'view_designs',
            'create_designs',
            'edit_designs',
            'delete_designs',
            'analyze_designs',

            // Component permissions
            'view_components',
            'create_components',
            'edit_components',
            'delete_components',
            'manage_component_library',
            'generate_code',

            // Collaboration permissions
            'view_comments',
            'create_comments',
            'edit_own_comments',
            'delete_own_comments',
            'delete_any_comments',
            'resolve_comments',
            'create_annotations',
            'delete_annotations',

            // Version control permissions
            'view_versions',
            'create_versions',
            'rollback_versions',
            'compare_versions',

            // User management permissions
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_roles',

            // Project permissions
            'view_projects',
            'create_projects',
            'edit_projects',
            'delete_projects',

            // Integration permissions
            'manage_integrations',
            'use_github_integration',
            'use_slack_integration',
            'configure_webhooks',

            // Settings permissions
            'view_settings',
            'edit_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin - Full access
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Manager - Can manage projects and users, but not system settings
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view_designs',
            'create_designs',
            'edit_designs',
            'delete_designs',
            'analyze_designs',
            'view_components',
            'create_components',
            'edit_components',
            'delete_components',
            'manage_component_library',
            'generate_code',
            'view_comments',
            'create_comments',
            'edit_own_comments',
            'delete_own_comments',
            'delete_any_comments',
            'resolve_comments',
            'create_annotations',
            'delete_annotations',
            'view_versions',
            'create_versions',
            'rollback_versions',
            'compare_versions',
            'view_users',
            'create_users',
            'edit_users',
            'view_projects',
            'create_projects',
            'edit_projects',
            'delete_projects',
            'use_github_integration',
            'use_slack_integration',
        ]);

        // Designer - Can create and edit designs, components, and collaborate
        $designer = Role::create(['name' => 'designer']);
        $designer->givePermissionTo([
            'view_designs',
            'create_designs',
            'edit_designs',
            'analyze_designs',
            'view_components',
            'create_components',
            'edit_components',
            'manage_component_library',
            'generate_code',
            'view_comments',
            'create_comments',
            'edit_own_comments',
            'delete_own_comments',
            'resolve_comments',
            'create_annotations',
            'view_versions',
            'create_versions',
            'compare_versions',
            'view_projects',
            'use_github_integration',
        ]);

        // Viewer - Read-only access
        $viewer = Role::create(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'view_designs',
            'view_components',
            'view_comments',
            'create_comments',
            'edit_own_comments',
            'view_versions',
            'view_projects',
        ]);
    }
}
