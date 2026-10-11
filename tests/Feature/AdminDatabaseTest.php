<?php

namespace Tests\Feature;

use Dcat\Admin\Models\Administrator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
class AdminDatabaseTest extends TestCase
{
    public function testAdminTablesCreated()
    {
        foreach ([
            'admin_users',
            'admin_roles',
            'admin_permissions',
            'admin_menu',
            'admin_role_users',
            'admin_role_permissions',
            'admin_role_menu',
            'admin_permission_menu',
            'admin_settings',
            'admin_extensions',
            'admin_extension_histories',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "table [$table] should exist");
        }
    }

    public function testAdministratorRelations()
    {
        $user = Administrator::create([
            'username' => 'relation-tester',
            'password' => Hash::make('secret123'),
            'name' => 'Relation Tester',
        ]);

        $role = $user->roles()->create(['name' => 'tester-role', 'slug' => 'tester-role']);
        $role->permissions()->create(['name' => 'tester-permission', 'slug' => 'tester-permission',
            'http_path' => '/admin*',
        ]);

        $user->refresh();

        $this->assertSame(1, $user->roles->count());
        $this->assertSame('tester-role', $user->roles->first()->slug);
        $this->assertTrue($user->isRole('tester-role'));
        $this->assertTrue(
            $user->roles->first()->permissions()->where('slug', 'tester-permission')->exists()
        );
    }
}
