<?php

namespace App\Http\Middleware;

use App\Domain\Dispatch\DueWorkSweeper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs time-due dispatch work off the back of ordinary traffic.
 *
 * The network has no cron and no queue worker, so nothing would otherwise
 * notice that an offer's deadline has passed. Every authenticated portal
 * request carries a little of that work instead.
 *
 * It runs *after* the response is prepared, so a sweep never delays the page
 * the operator asked for — and it is throttled inside the sweeper, so a board
 * polling every fifteen seconds does not sweep every fifteen seconds.
 */
class SweepDueWork
{
    public function __construct(
        private readonly DueWorkSweeper $sweeper,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Livewire polls and navigations are exactly the steady heartbeat this
        // wants, but a static asset or a redirect is not worth sweeping on.
        if ($request->isMethod('GET') || $request->hasHeader('X-Livewire')) {
            $this->sweeper->sweep();
        }

        return $response;
    }
}
