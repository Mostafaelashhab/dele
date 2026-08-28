<?php

namespace App\Livewire\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Matching\MatchingEngine;
use App\Enums\AuditAction;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\PlatformSetting as SettingModel;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Runtime overrides for the values that shape dispatch.
 *
 * These exist so the network can be retuned during a pilot — widening the
 * fan-out, lengthening a timeout, shifting weight from price to speed —
 * without a deployment, and with every change recorded in the audit trail.
 */
class PlatformSettings extends Component
{
    use UsesPortalLayout;

    /**
     * @var array<string, float>
     */
    public array $weights = [];

    public int $offerTimeout = 90;

    public int $companiesPerRound = 2;

    public int $maxRounds = 4;

    public int $riderOfferTimeout = 60;

    public int $pingInterval = 15;

    public int $platformFeeBps = 1200;

    public int $riderShareBps = 7000;

    public function mount(): void
    {
        $this->weights = MatchingEngine::weights();

        $this->offerTimeout = (int) SettingModel::get('dispatch.offer_timeout_seconds', config('platform.dispatch.offer_timeout_seconds'));
        $this->companiesPerRound = (int) SettingModel::get('dispatch.companies_per_round', config('platform.dispatch.companies_per_round'));
        $this->maxRounds = (int) SettingModel::get('dispatch.max_rounds', config('platform.dispatch.max_rounds'));
        $this->riderOfferTimeout = (int) SettingModel::get('dispatch.rider_offer_timeout_seconds', config('platform.dispatch.rider_offer_timeout_seconds'));
        $this->pingInterval = (int) SettingModel::get('tracking.ping_interval_seconds', config('platform.tracking.ping_interval_seconds'));
        $this->platformFeeBps = (int) SettingModel::get('pricing.platform_fee.percentage_bps', config('platform.pricing.platform_fee.percentage_bps'));
        $this->riderShareBps = (int) SettingModel::get('settlements.rider_share_bps', config('platform.settlements.rider_share_bps'));
    }

    public function save(): void
    {
        $validated = $this->validate([
            'weights.*' => ['required', 'numeric', 'min:0', 'max:1'],
            'offerTimeout' => ['required', 'integer', 'min:30', 'max:600'],
            'companiesPerRound' => ['required', 'integer', 'min:1', 'max:10'],
            'maxRounds' => ['required', 'integer', 'min:1', 'max:10'],
            'riderOfferTimeout' => ['required', 'integer', 'min:20', 'max:300'],
            'pingInterval' => ['required', 'integer', 'min:5', 'max:120'],
            'platformFeeBps' => ['required', 'integer', 'min:0', 'max:5000'],
            'riderShareBps' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        // Weights are normalised on save so the sum is always exactly 1 and a
        // fat-fingered entry cannot silently inflate every candidate's score.
        $total = array_sum(array_map('floatval', $validated['weights']));

        $normalised = $total > 0
            ? array_map(fn (float|string $value) => round(((float) $value) / $total, 4), $validated['weights'])
            : $validated['weights'];

        $userId = auth()->id();

        SettingModel::put('matching.weights', $normalised, 'matching', $userId);
        SettingModel::put('dispatch.offer_timeout_seconds', $validated['offerTimeout'], 'dispatch', $userId);
        SettingModel::put('dispatch.companies_per_round', $validated['companiesPerRound'], 'dispatch', $userId);
        SettingModel::put('dispatch.max_rounds', $validated['maxRounds'], 'dispatch', $userId);
        SettingModel::put('dispatch.rider_offer_timeout_seconds', $validated['riderOfferTimeout'], 'dispatch', $userId);
        SettingModel::put('tracking.ping_interval_seconds', $validated['pingInterval'], 'tracking', $userId);
        SettingModel::put('pricing.platform_fee.percentage_bps', $validated['platformFeeBps'], 'pricing', $userId);
        SettingModel::put('settlements.rider_share_bps', $validated['riderShareBps'], 'settlements', $userId);

        $this->weights = $normalised;

        app(AuditLogger::class)->log(
            action: AuditAction::Updated,
            description: 'Platform settings updated.',
            newValues: [
                'weights' => $normalised,
                'dispatch' => [
                    'offer_timeout' => $validated['offerTimeout'],
                    'companies_per_round' => $validated['companiesPerRound'],
                    'max_rounds' => $validated['maxRounds'],
                ],
            ],
        );

        session()->flash('status', __('app.common.save'));
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.platform-settings', title: __('app.nav.settings'));
    }
}
