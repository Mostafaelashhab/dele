<?php

namespace App\Livewire\Admin;

use App\Domain\Analytics\PlatformMetrics;
use App\Livewire\Concerns\UsesPortalLayout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The KPIs the pilot is measured on: how fast, how reliable, how much, and
 * which companies are actually carrying the network.
 */
class Analytics extends Component
{
    use UsesPortalLayout;

    #[Url(except: '30')]
    public string $days = '30';

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function window(): array
    {
        return [today()->subDays((int) $this->days - 1)->startOfDay(), today()->endOfDay()];
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function overview(): array
    {
        [$from, $to] = $this->window();

        return app(PlatformMetrics::class)->overview($from, $to);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function series(): Collection
    {
        [$from, $to] = $this->window();

        return app(PlatformMetrics::class)->dailySeries($from, $to);
    }

    /**
     * The daily series, shaped for the column chart.
     *
     * Two series only — volume and failures — because that is the question the
     * chart answers. Anything finer belongs in the table beneath it.
     *
     * @return array<int, array{label: string, full: string, values: array<string, int>}>
     */
    #[Computed]
    public function dailyRows(): array
    {
        return $this->series()
            ->map(fn (array $day) => [
                'label' => Carbon::parse($day['date'])->translatedFormat('j'),
                'full' => Carbon::parse($day['date'])->translatedFormat('D j M'),
                'values' => [
                    'delivered' => $day['delivered'],
                    'failed' => $day['failed'],
                ],
            ])
            ->all();
    }

    /**
     * Daily completed volume, for the revenue sparkline.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function volumeTrend(): array
    {
        return $this->series()
            ->map(fn (array $day) => $day['volume']->minor)
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function companies(): Collection
    {
        [$from, $to] = $this->window();

        return app(PlatformMetrics::class)->companyPerformance($from, $to);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function businesses(): Collection
    {
        [$from, $to] = $this->window();

        return app(PlatformMetrics::class)->businessVolume($from, $to);
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.analytics', title: __('app.nav.analytics'));
    }
}
