<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * The PWA manifest, generated so the app name and colours follow the
 * platform's own configuration rather than a checked-in copy of them.
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => __('rider.app.name').' — '.__('app.name'),
            'short_name' => __('rider.app.name'),
            'description' => __('rider.app.description'),
            'start_url' => route('rider.home', absolute: false),
            'scope' => '/rider',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#0d1117',
            'theme_color' => '#0d1117',
            'lang' => app()->getLocale(),
            'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            'categories' => ['business', 'productivity'],
            'icons' => [
                [
                    'src' => '/icons/rider-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => '/icons/rider-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => __('rider.app.available_deliveries'),
                    'url' => route('rider.home', absolute: false),
                ],
                [
                    'name' => __('app.nav.earnings'),
                    'url' => route('rider.earnings', absolute: false),
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
