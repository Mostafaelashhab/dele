<?php

namespace Tests;

use App\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Roles and permissions are reference data the application assumes exists,
     * so tests that touch registration or authorization seed them once rather
     * than each asserting their own copy.
     */
    protected function seedRoles(): void
    {
        if (Role::query()->exists()) {
            return;
        }

        $this->seed(RolePermissionSeeder::class);
    }
}
