<?php

namespace App\Livewire\Concerns;

use App\Domain\Audit\AuditLogger;
use App\Domain\Zones\ZoneResolver;
use App\Enums\AuditAction;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PricingRuleType;
use App\Models\PricingRule;
use App\Models\Zone;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Shared pricing-rule editing for the platform and company portals.
 *
 * Both edit the same table with the same validation; only the owning scope
 * differs, which is supplied by the component. Keeping one implementation
 * means a platform rule and a company rule can never drift apart in what they
 * accept or how they are audited.
 */
trait ManagesPricingRules
{
    public bool $editing = false;

    public ?string $ruleId = null;

    public string $ruleName = '';

    public string $ruleType = PricingRuleType::BaseFare->value;

    public string $pickupZoneId = '';

    public string $dropoffZoneId = '';

    public string $rulePriority = '';

    public string $rulePackageSize = '';

    public string $amount = '0';

    public string $ratePerKm = '0';

    public string $percentageBps = '0';

    public string $freeUnits = '0';

    public bool $ruleActive = true;

    /**
     * The delivery company these rules belong to, or null for platform-wide
     * defaults.
     */
    abstract protected function pricingCompanyId(): ?string;

    /**
     * @return Collection<int, PricingRule>
     */
    #[Computed]
    public function rules(): Collection
    {
        return PricingRule::query()
            ->where('delivery_company_id', $this->pricingCompanyId())
            ->with(['pickupZone', 'dropoffZone'])
            ->orderBy('evaluation_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Zone>
     */
    #[Computed]
    public function pricingZones(): Collection
    {
        return app(ZoneResolver::class)->activeZones();
    }

    public function newRule(): void
    {
        $this->resetRuleForm();
        $this->editing = true;
    }

    public function editRule(string $id): void
    {
        $rule = $this->findRule($id);

        $this->ruleId = $rule->id;
        $this->ruleName = $rule->name;
        $this->ruleType = $rule->type->value;
        $this->pickupZoneId = (string) $rule->pickup_zone_id;
        $this->dropoffZoneId = (string) $rule->dropoff_zone_id;
        $this->rulePriority = $rule->priority?->value ?? '';
        $this->rulePackageSize = $rule->package_size?->value ?? '';
        $this->amount = (string) (($rule->amount_minor?->minor ?? 0) / 100);
        $this->ratePerKm = (string) ($rule->rate_minor_per_km / 100);
        $this->percentageBps = (string) ($rule->percentage_bps / 100);
        $this->freeUnits = (string) $rule->free_units;
        $this->ruleActive = $rule->is_active;
        $this->editing = true;
    }

    public function saveRule(): void
    {
        $validated = $this->validate([
            'ruleName' => ['required', 'string', 'max:120'],
            'ruleType' => ['required', Rule::enum(PricingRuleType::class)],
            'pickupZoneId' => ['nullable', 'string', 'exists:zones,id'],
            'dropoffZoneId' => ['nullable', 'string', 'exists:zones,id'],
            'rulePriority' => ['nullable', Rule::enum(DeliveryPriority::class)],
            'rulePackageSize' => ['nullable', Rule::enum(PackageSize::class)],
            'amount' => ['required', 'numeric', 'min:0', 'max:100000'],
            'ratePerKm' => ['required', 'numeric', 'min:0', 'max:10000'],
            'percentageBps' => ['required', 'numeric', 'min:-100', 'max:500'],
            'freeUnits' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $type = PricingRuleType::from($validated['ruleType']);

        $attributes = [
            'name' => $validated['ruleName'],
            'type' => $type,
            'delivery_company_id' => $this->pricingCompanyId(),
            'pickup_zone_id' => $validated['pickupZoneId'] ?: null,
            'dropoff_zone_id' => $validated['dropoffZoneId'] ?: null,
            'priority' => $validated['rulePriority'] ?: null,
            'package_size' => $validated['rulePackageSize'] ?: null,
            'amount_minor' => (int) round(((float) $validated['amount']) * 100),
            'rate_minor_per_km' => (int) round(((float) $validated['ratePerKm']) * 100),
            'percentage_bps' => (int) round(((float) $validated['percentageBps']) * 100),
            'free_units' => (int) $validated['freeUnits'],
            'evaluation_order' => $type->evaluationOrder(),
            'is_active' => $this->ruleActive,
        ];

        $rule = $this->ruleId === null
            ? PricingRule::create($attributes)
            : tap($this->findRule($this->ruleId))->update($attributes);

        app(AuditLogger::class)->log(
            action: AuditAction::PricingChanged,
            entity: $rule,
            description: __('audit.description.pricing_updated', ['rule' => $rule->name]),
            newValues: $attributes,
            tenantType: $this->pricingCompanyId() === null ? null : 'delivery_company',
            tenantId: $this->pricingCompanyId(),
        );

        $this->resetRuleForm();
        unset($this->rules);

        session()->flash('status', __('app.common.save'));
    }

    public function toggleRule(string $id): void
    {
        $rule = $this->findRule($id);
        $rule->update(['is_active' => ! $rule->is_active]);

        app(AuditLogger::class)->log(
            action: AuditAction::PricingChanged,
            entity: $rule,
            newValues: ['is_active' => $rule->is_active],
        );

        unset($this->rules);
    }

    public function deleteRule(string $id): void
    {
        $rule = $this->findRule($id);

        app(AuditLogger::class)->log(
            action: AuditAction::Deleted,
            entity: $rule,
            oldValues: $rule->only(['name', 'type', 'amount_minor']),
        );

        $rule->delete();
        unset($this->rules);
    }

    protected function resetRuleForm(): void
    {
        $this->reset([
            'editing', 'ruleId', 'ruleName', 'pickupZoneId', 'dropoffZoneId',
            'rulePriority', 'rulePackageSize', 'amount', 'ratePerKm',
            'percentageBps', 'freeUnits',
        ]);

        $this->ruleType = PricingRuleType::BaseFare->value;
        $this->ruleActive = true;
    }

    /**
     * Scoped lookup: a company can only ever reach its own rules.
     */
    protected function findRule(string $id): PricingRule
    {
        return PricingRule::query()
            ->whereKey($id)
            ->where('delivery_company_id', $this->pricingCompanyId())
            ->firstOrFail();
    }
}
