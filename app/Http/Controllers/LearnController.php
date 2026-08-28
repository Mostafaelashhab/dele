<?php

namespace App\Http\Controllers;

use App\Domain\Matching\MatchingEngine;
use App\Domain\Zones\ZoneResolver;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The manual.
 *
 * The landing page argues; this explains. Everything a person needs to know
 * before committing lives here, written for one role at a time — a shop owner
 * reading about rider identity checks is reading somebody else's manual, and
 * a page that mixes all four teaches none of them.
 *
 * The content is data rather than four near-identical templates, so a step
 * added for one role cannot silently diverge in layout from the others.
 */
class LearnController extends Controller
{
    /**
     * The four manuals, and the screen each step is illustrated with.
     *
     * Kept here rather than in the language files because the *shape* of a
     * lesson — which step shows which screen — is a product decision, not a
     * translation. The words live in the learn.php language files.
     *
     * @var array<string, array<string, mixed>>
     */
    private const AUDIENCES = [
        'individual' => [
            'icon' => 'user',
            'accent' => 'signal',
            'route' => 'register.individual',
            'steps' => 5,
            'screens' => [1 => 'order', 3 => 'tracking'],
        ],
        'business' => [
            'icon' => 'store',
            'accent' => 'signal',
            'route' => 'register.business',
            'steps' => 6,
            'screens' => [1 => 'order', 2 => 'dispatch', 4 => 'tracking'],
        ],
        'company' => [
            'icon' => 'truck',
            'accent' => 'ember',
            'route' => 'register.company',
            'steps' => 6,
            'screens' => [2 => 'dispatch', 4 => 'rider'],
        ],
        'rider' => [
            'icon' => 'motorcycle',
            'accent' => 'emerald',
            'route' => 'register.rider',
            'steps' => 6,
            'screens' => [3 => 'rider'],
        ],
    ];

    /**
     * The hub: pick which manual is yours.
     */
    public function index(): View
    {
        return view('public.learn.index', [
            'audiences' => collect(self::AUDIENCES)
                ->map(fn (array $meta, string $key) => $meta + ['key' => $key])
                ->values()
                ->all(),
        ]);
    }

    public function show(string $audience): View
    {
        if (! array_key_exists($audience, self::AUDIENCES)) {
            throw new NotFoundHttpException;
        }

        $meta = self::AUDIENCES[$audience];

        return view('public.learn.show', [
            'audience' => $audience,
            'meta' => $meta + ['key' => $audience],
            'steps' => $this->steps($audience, $meta),
            'others' => collect(self::AUDIENCES)
                ->except($audience)
                ->map(fn (array $other, string $key) => $other + ['key' => $key])
                ->values()
                ->all(),
            // Shown on the company manual, where "how am I ranked" is the
            // question that decides whether they sign up.
            'weights' => $audience === 'company' ? $this->weights() : [],
            'zones' => app(ZoneResolver::class)->activeZones(),
        ]);
    }

    /**
     * One lesson's steps, paired with the screen each is illustrated with.
     *
     * @param  array<string, mixed>  $meta
     * @return array<int, array<string, mixed>>
     */
    private function steps(string $audience, array $meta): array
    {
        return collect(range(1, $meta['steps']))
            ->map(fn (int $number) => [
                'number' => $number,
                'title' => __("learn.{$audience}.steps.{$number}.title"),
                'body' => __("learn.{$audience}.steps.{$number}.body"),
                'points' => (array) __("learn.{$audience}.steps.{$number}.points"),
                'screen' => $meta['screens'][$number] ?? null,
            ])
            ->all();
    }

    /**
     * The live ranking weights, so the company manual explains the real rule.
     *
     * @return array<int, array{label: string, percentage: int}>
     */
    private function weights(): array
    {
        $weights = MatchingEngine::weights();
        $total = array_sum($weights) ?: 1;

        return collect($weights)
            ->map(fn (float $weight, string $key) => [
                'label' => __('offer.factor.'.$key),
                'percentage' => (int) round(($weight / $total) * 100),
            ])
            ->sortByDesc('percentage')
            ->values()
            ->all();
    }
}
