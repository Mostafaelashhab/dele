<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\CurrentTenant;
use App\Enums\AccountStatus;
use App\Models\DeliveryCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCompanyTenant
{
    public function __construct(
        private readonly CurrentTenant $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $user->is_active, 403);

        $membership = $user->companyMemberships()
            ->where('is_active', true)
            ->when(
                $request->session()->get('delivery_company_id'),
                fn ($query, $id) => $query->where('delivery_company_id', $id)
            )
            ->with('deliveryCompany')
            ->first();

        $membership ??= $user->companyMemberships()
            ->where('is_active', true)
            ->with('deliveryCompany')
            ->first();

        abort_if($membership?->deliveryCompany === null, 403);

        /** @var DeliveryCompany $company */
        $company = $membership->deliveryCompany;

        abort_if($company->status === AccountStatus::Closed, 403);

        if ($company->status === AccountStatus::Suspended) {
            abort(403, __('app.auth.inactive'));
        }

        $request->session()->put('delivery_company_id', $company->id);
        $this->tenant->setCompany($company);

        return $next($request);
    }
}
