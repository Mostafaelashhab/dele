<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * The sitemap, generated from the routes that actually exist.
 *
 * Written rather than hand-maintained so a page cannot be renamed into a
 * broken sitemap entry, and deliberately short: only the pages a stranger
 * should be able to arrive at. Tracking links carry a customer's address
 * behind a token and are never listed.
 */
class SitemapController extends Controller
{
    /**
     * Public routes, with how often each is worth recrawling.
     *
     * @var array<string, array{changefreq: string, priority: string}>
     */
    private const PAGES = [
        'home' => ['changefreq' => 'daily', 'priority' => '1.0'],
        'learn' => ['changefreq' => 'monthly', 'priority' => '0.9'],
        'coverage' => ['changefreq' => 'weekly', 'priority' => '0.8'],
        'faq' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        'register' => ['changefreq' => 'monthly', 'priority' => '0.8'],
        'register.business' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        'register.company' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        'login' => ['changefreq' => 'yearly', 'priority' => '0.3'],
    ];

    /**
     * The per-role guides, which are real indexable pages rather than
     * fragments of one long document.
     *
     * @var array<int, string>
     */
    private const GUIDES = ['individual', 'business', 'company', 'rider'];

    public function __invoke(): Response
    {
        $urls = collect(self::PAGES)
            ->filter(fn (array $meta, string $name) => Route::has($name))
            ->map(fn (array $meta, string $name) => [
                'loc' => route($name),
                'changefreq' => $meta['changefreq'],
                'priority' => $meta['priority'],
            ])
            ->values()
            ->merge(collect(self::GUIDES)->map(fn (string $audience) => [
                'loc' => route('learn.show', $audience),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ]))
            ->values();

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
