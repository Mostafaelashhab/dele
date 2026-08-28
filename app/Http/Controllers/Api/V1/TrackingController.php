<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tracking\TrackingPresenter;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;

/**
 * Unauthenticated tracking, for a business embedding live status in its own
 * order page. The token is the credential; the presenter decides what a
 * holder of that token is allowed to see.
 */
class TrackingController extends Controller
{
    public function __invoke(string $token, TrackingPresenter $presenter): JsonResponse
    {
        $delivery = Delivery::query()
            ->where('tracking_token', $token)
            ->with(['order', 'business', 'deliveryCompany', 'rider'])
            ->first();

        if ($delivery === null) {
            return response()->json([
                'error' => ['type' => 'not_found', 'message' => __('app.tracking.not_found')],
            ], 404);
        }

        return response()->json(['data' => $presenter->present($delivery)]);
    }
}
