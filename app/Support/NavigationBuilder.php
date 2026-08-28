<?php

namespace App\Support;

use App\Models\User;

/**
 * Builds the sidebar for whichever portal the user is in.
 *
 * Navigation is derived rather than hard-coded per view so a route added to a
 * portal appears in exactly one place, and so items a user cannot reach are
 * never rendered — the menu and the authorization agree by construction.
 */
class NavigationBuilder
{
    /**
     * @return array<int, array{label: string, items: array<int, array{route: string, label: string, icon: string, badge?: int|null}>}>
     */
    public function for(string $portal, ?User $user = null): array
    {
        return match ($portal) {
            'admin' => $this->admin(),
            'business' => $this->business(),
            'company' => $this->company(),
            default => [],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function admin(): array
    {
        return [
            [
                'label' => __('app.dashboard.live_operations'),
                'items' => [
                    ['route' => 'admin.dashboard', 'label' => __('app.nav.dashboard'), 'icon' => 'dashboard'],
                    ['route' => 'admin.live', 'label' => __('app.nav.live'), 'icon' => 'map'],
                    ['route' => 'admin.review', 'label' => __('review.title'), 'icon' => 'shield'],
                    ['route' => 'admin.orders.index', 'label' => __('app.nav.orders'), 'icon' => 'package'],
                ],
            ],
            [
                'label' => __('app.nav.businesses'),
                'items' => [
                    ['route' => 'admin.businesses.index', 'label' => __('app.nav.businesses'), 'icon' => 'store'],
                    ['route' => 'admin.companies.index', 'label' => __('app.nav.companies'), 'icon' => 'truck'],
                    ['route' => 'admin.riders.index', 'label' => __('app.nav.riders'), 'icon' => 'users'],
                ],
            ],
            [
                'label' => __('app.nav.settings'),
                'items' => [
                    ['route' => 'admin.zones.index', 'label' => __('app.nav.zones'), 'icon' => 'zones'],
                    ['route' => 'admin.pricing.index', 'label' => __('app.nav.pricing'), 'icon' => 'money'],
                    ['route' => 'admin.settlements.index', 'label' => __('app.nav.settlements'), 'icon' => 'receipt'],
                    ['route' => 'admin.analytics', 'label' => __('app.nav.analytics'), 'icon' => 'chart'],
                    ['route' => 'admin.audit.index', 'label' => __('app.nav.audit'), 'icon' => 'shield'],
                    ['route' => 'admin.settings.index', 'label' => __('app.nav.settings'), 'icon' => 'settings'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function business(): array
    {
        return [
            [
                'label' => null,
                'items' => [
                    ['route' => 'business.dashboard', 'label' => __('app.nav.dashboard'), 'icon' => 'dashboard'],
                    ['route' => 'business.orders.index', 'label' => __('app.nav.orders'), 'icon' => 'package'],
                    ['route' => 'business.addresses.index', 'label' => __('app.nav.addresses'), 'icon' => 'pin'],
                    ['route' => 'business.customers.index', 'label' => __('app.nav.customers'), 'icon' => 'users'],
                ],
            ],
            [
                'label' => __('app.nav.settings'),
                'items' => [
                    ['route' => 'business.finance', 'label' => __('app.nav.finance'), 'icon' => 'receipt'],
                    ['route' => 'business.team.index', 'label' => __('app.nav.team'), 'icon' => 'user'],
                    ['route' => 'business.api.index', 'label' => __('app.nav.api'), 'icon' => 'code'],
                    ['route' => 'business.settings', 'label' => __('app.nav.settings'), 'icon' => 'settings'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function company(): array
    {
        return [
            [
                'label' => null,
                'items' => [
                    ['route' => 'company.dashboard', 'label' => __('app.nav.dashboard'), 'icon' => 'dashboard'],
                    ['route' => 'company.offers.index', 'label' => __('app.nav.offers'), 'icon' => 'bell'],
                    ['route' => 'company.deliveries.index', 'label' => __('app.nav.deliveries'), 'icon' => 'package'],
                    ['route' => 'company.riders.index', 'label' => __('app.nav.riders'), 'icon' => 'users'],
                ],
            ],
            [
                'label' => __('app.nav.settings'),
                'items' => [
                    ['route' => 'company.service-areas', 'label' => __('app.nav.service_areas'), 'icon' => 'zones'],
                    ['route' => 'company.pricing.index', 'label' => __('app.nav.pricing'), 'icon' => 'money'],
                    ['route' => 'company.settlements.index', 'label' => __('app.nav.settlements'), 'icon' => 'receipt'],
                    ['route' => 'company.settings', 'label' => __('app.nav.settings'), 'icon' => 'settings'],
                ],
            ],
        ];
    }
}
