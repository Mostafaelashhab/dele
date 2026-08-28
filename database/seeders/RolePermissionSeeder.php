<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Roles and the permissions attached to them.
 *
 * Idempotent: re-running updates the definitions rather than duplicating
 * them, so this can be part of every deploy.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    private const PERMISSION_GROUPS = [
        'orders' => ['orders.view', 'orders.create', 'orders.cancel', 'orders.dispatch'],
        'deliveries' => ['deliveries.view', 'deliveries.assign', 'deliveries.update'],
        'offers' => ['offers.view', 'offers.respond'],
        'businesses' => ['businesses.view', 'businesses.manage', 'businesses.suspend'],
        'companies' => ['companies.view', 'companies.manage', 'companies.suspend'],
        'riders' => ['riders.view', 'riders.manage'],
        'pricing' => ['pricing.view', 'pricing.manage'],
        'zones' => ['zones.view', 'zones.manage'],
        'finance' => ['finance.view', 'finance.settle'],
        'analytics' => ['analytics.view'],
        'api' => ['api.view', 'api.manage'],
        'audit' => ['audit.view'],
        'settings' => ['settings.manage'],
    ];

    /**
     * @var array<string, array{name: string, name_ar: string, scope: string, permissions: array<int, string>|string}>
     */
    private const ROLES = [
        UserRole::PlatformAdmin->value => [
            'name' => 'Platform administrator',
            'name_ar' => 'مدير المنصة',
            'scope' => 'platform',
            'permissions' => '*',
        ],
        UserRole::PlatformOperator->value => [
            'name' => 'Platform operator',
            'name_ar' => 'مشغّل المنصة',
            'scope' => 'platform',
            'permissions' => [
                'orders.view', 'orders.cancel', 'orders.dispatch', 'deliveries.view',
                'deliveries.assign', 'businesses.view', 'companies.view', 'riders.view',
                'zones.view', 'pricing.view', 'analytics.view', 'audit.view',
            ],
        ],
        UserRole::PlatformSupport->value => [
            'name' => 'Support',
            'name_ar' => 'دعم فني',
            'scope' => 'platform',
            'permissions' => ['orders.view', 'deliveries.view', 'businesses.view', 'companies.view'],
        ],
        UserRole::BusinessOwner->value => [
            'name' => 'Business owner',
            'name_ar' => 'صاحب النشاط',
            'scope' => 'business',
            'permissions' => [
                'orders.view', 'orders.create', 'orders.cancel', 'deliveries.view',
                'finance.view', 'api.view', 'api.manage', 'settings.manage', 'analytics.view',
            ],
        ],
        UserRole::BusinessStaff->value => [
            'name' => 'Business staff',
            'name_ar' => 'موظف',
            'scope' => 'business',
            'permissions' => ['orders.view', 'orders.create', 'deliveries.view'],
        ],
        UserRole::CompanyOwner->value => [
            'name' => 'Company owner',
            'name_ar' => 'صاحب شركة التوصيل',
            'scope' => 'delivery_company',
            'permissions' => [
                'offers.view', 'offers.respond', 'deliveries.view', 'deliveries.assign',
                'deliveries.update', 'riders.view', 'riders.manage', 'pricing.view',
                'pricing.manage', 'finance.view', 'settings.manage', 'analytics.view',
            ],
        ],
        UserRole::CompanyDispatcher->value => [
            'name' => 'Dispatcher',
            'name_ar' => 'موزّع الطلبات',
            'scope' => 'delivery_company',
            'permissions' => [
                'offers.view', 'offers.respond', 'deliveries.view',
                'deliveries.assign', 'deliveries.update', 'riders.view',
            ],
        ],
        UserRole::Rider->value => [
            'name' => 'Rider',
            'name_ar' => 'مندوب توصيل',
            'scope' => 'delivery_company',
            'permissions' => ['deliveries.view', 'deliveries.update'],
        ],
    ];

    public function run(): void
    {
        $permissions = collect(self::PERMISSION_GROUPS)
            ->flatMap(fn (array $slugs, string $group) => collect($slugs)->map(
                fn (string $slug) => Permission::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => Str::headline(str_replace('.', ' ', $slug)), 'group' => $group],
                )
            ))
            ->keyBy('slug');

        foreach (self::ROLES as $slug => $definition) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'name_ar' => $definition['name_ar'],
                    'scope' => $definition['scope'],
                ],
            );

            $granted = $definition['permissions'] === '*'
                ? $permissions->pluck('id')
                : $permissions->only($definition['permissions'])->pluck('id');

            $role->permissions()->sync($granted);
        }
    }
}
