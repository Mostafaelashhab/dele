<?php

namespace App\Livewire\Company;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\AuditAction;
use App\Livewire\Concerns\UsesPortalLayout;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Operating settings a company controls for itself: how it is reached, when
 * it is open, how much work it will hold, and whether the platform may assign
 * its riders automatically.
 */
class Settings extends Component
{
    use UsesPortalLayout, WithFileUploads;

    public string $contactPerson = '';

    public string $phone = '';

    public string $email = '';

    public string $addressLine = '';

    public bool $autoAccept = false;

    public int $maxConcurrent = 50;

    public int $offerTimeout = 90;

    public $logo = null;

    /**
     * @var array<string, array{closed: bool, opens: string, closes: string}>
     */
    public array $hours = [];

    private const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function mount(): void
    {
        $company = app(CurrentTenant::class)->companyOrFail();

        $this->contactPerson = (string) $company->contact_person;
        $this->phone = $company->phone;
        $this->email = (string) $company->email;
        $this->addressLine = (string) $company->address_line;
        $this->autoAccept = $company->auto_accept_offers;
        $this->maxConcurrent = $company->max_concurrent_deliveries;
        $this->offerTimeout = $company->offerTimeoutSeconds();

        $stored = $company->working_hours ?? [];

        foreach (self::DAYS as $day) {
            $this->hours[$day] = [
                'closed' => (bool) ($stored[$day]['closed'] ?? false),
                'opens' => (string) ($stored[$day]['opens'] ?? '09:00'),
                'closes' => (string) ($stored[$day]['closes'] ?? '23:00'),
            ];
        }
    }

    public function save(): void
    {
        $company = app(CurrentTenant::class)->companyOrFail();

        $validated = $this->validate([
            'contactPerson' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^01[0-2,5]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'addressLine' => ['nullable', 'string', 'max:255'],
            'maxConcurrent' => ['required', 'integer', 'min:1', 'max:500'],
            'offerTimeout' => ['required', 'integer', 'min:30', 'max:600'],
            'hours.*.opens' => ['required', 'date_format:H:i'],
            'hours.*.closes' => ['required', 'date_format:H:i'],
            'logo' => ['nullable', 'image', 'max:'.(int) config('platform.media.max_upload_kb', 4096)],
        ]);

        $company->update([
            'contact_person' => $validated['contactPerson'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?: null,
            'address_line' => $validated['addressLine'] ?: null,
            'auto_accept_offers' => $this->autoAccept,
            'max_concurrent_deliveries' => $validated['maxConcurrent'],
            'offer_timeout_seconds' => $validated['offerTimeout'],
            'working_hours' => $this->hours,
        ]);

        if ($this->logo instanceof TemporaryUploadedFile) {
            $company->storeMedia('logo_path', $this->logo, 'logos/company');
            $this->logo = null;
        }

        app(AuditLogger::class)->logChanges(
            action: AuditAction::Updated,
            entity: $company,
            description: 'Company settings updated.',
        );

        session()->flash('status', __('app.common.save'));
    }

    public function render(): View
    {
        return $this->portalView('livewire.company.settings', [
            'days' => self::DAYS,
        ], __('app.nav.settings'));
    }
}
