<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\CurrentTenant;
use App\Enums\RiderStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveRider
{
    public function __construct(
        private readonly CurrentTenant $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $user->is_active, 403);

        $rider = $user->rider()->with('deliveryCompany')->first();

        abort_if($rider === null, 403);
        abort_if($rider->status === RiderStatus::Suspended, 403, __('app.auth.inactive'));

        $this->tenant->setRider($rider);
        $this->tenant->setCompany($rider->deliveryCompany);

        // A rider with the app open is, by definition, reachable.
        $rider->forceFill(['last_seen_at' => now()])->saveQuietly();

        return $next($request);
    }
}
