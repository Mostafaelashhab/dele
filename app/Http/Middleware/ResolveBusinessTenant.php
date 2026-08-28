<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\CurrentTenant;
use App\Enums\AccountStatus;
use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the signed-in user to exactly one business for the duration of the
 * request. This is the single place a business is chosen, which is what makes
 * cross-tenant access a routing impossibility rather than a review checklist.
 */
class ResolveBusinessTenant
{
    public function __construct(
        private readonly CurrentTenant $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $user->is_active, 403);

        $membership = $user->businessMemberships()
            ->where('is_active', true)
            ->when(
                $request->session()->get('business_id'),
                fn ($query, $id) => $query->where('business_id', $id)
            )
            ->with('business')
            ->first();

        // Falling back covers a user whose session points at a business they
        // have since been removed from.
        $membership ??= $user->businessMemberships()
            ->where('is_active', true)
            ->with('business')
            ->first();

        abort_if($membership?->business === null, 403);

        /** @var Business $business */
        $business = $membership->business;

        abort_if($business->status === AccountStatus::Closed, 403);

        if ($business->status === AccountStatus::Suspended) {
            abort(403, __('app.auth.inactive'));
        }

        $request->session()->put('business_id', $business->id);
        $this->tenant->setBusiness($business);

        return $next($request);
    }
}
