<?php

namespace App\Livewire\Concerns;

use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Contracts\View\View;

/**
 * Renders a Livewire component inside the right portal shell.
 *
 * Keeps the layout name, the portal key and the tenant label in one place so
 * a component never has to restate them — and so a company page cannot
 * accidentally render with the business sidebar.
 */
trait UsesPortalLayout
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function portalView(string $view, array $data = [], ?string $title = null): View
    {
        return view($view, $data)->layout('components.layouts.app', [
            'portal' => $this->portal(),
            'title' => $title,
            'context' => $this->tenantLabel(),
        ]);
    }

    /**
     * Derived from the component's namespace, so a component placed in the
     * company tree cannot render in the business shell.
     */
    protected function portal(): string
    {
        return match (true) {
            str_contains(static::class, '\\Admin\\') => 'admin',
            str_contains(static::class, '\\Company\\') => 'company',
            default => 'business',
        };
    }

    protected function tenantLabel(): ?string
    {
        $tenant = app(CurrentTenant::class);

        return match ($this->portal()) {
            'company' => $tenant->company()?->displayName(),
            'business' => $tenant->business()?->displayName(),
            default => __('app.nav.dashboard'),
        };
    }
}
