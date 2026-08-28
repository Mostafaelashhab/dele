<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Order matters: roles before users, zones before anything that resolves a
 * location, and pricing before any order is created and priced.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ZoneSeeder::class,
            PricingSeeder::class,
            NetworkSeeder::class,
        ]);
    }
}
