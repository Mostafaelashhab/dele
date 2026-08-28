<?php

namespace App\Livewire\Admin;

use App\Enums\AuditAction;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AuditTrail extends Component
{
    use UsesPortalLayout, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $action = '';

    #[Url(except: '')]
    public string $from = '';

    public ?string $expanded = null;

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function entries(): LengthAwarePaginator
    {
        return AuditLog::query()
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner
                    ->where('actor_label', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhere('entity_id', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(40);
    }

    public function toggle(string $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.audit-trail', [
            'entries' => $this->entries(),
            'actions' => AuditAction::cases(),
        ], __('app.nav.audit'));
    }
}
