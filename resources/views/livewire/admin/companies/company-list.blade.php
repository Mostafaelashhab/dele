<div>
    <x-ui.page-header :title="__('app.nav.companies')" :subtitle="__('app.intro.company_list')">
        <x-slot:actions>
            <x-ui.button :href="route('admin.companies.onboard')" icon="plus">
                {{ __('app.common.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card flush>
        @if ($this->companies->isEmpty())
            <x-ui.empty icon="truck" :title="__('app.common.empty')">
                <x-ui.button :href="route('admin.companies.onboard')" size="sm" icon="plus">
                    {{ __('app.common.create') }}
                </x-ui.button>
            </x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>{{ __('app.common.phone') }}</th>
                            <th class="text-end">{{ __('app.nav.riders') }}</th>
                            <th class="text-end">{{ __('app.nav.deliveries') }}</th>
                            <th class="text-end">{{ __('app.dashboard.acceptance_rate') }}</th>
                            <th class="text-end">{{ __('app.dashboard.completion_rate') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->companies as $company)
                            <tr wire:key="{{ $company->id }}">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <x-ui.avatar
                                            :src="$company->mediaUrl('logo_path')"
                                            :name="$company->displayName()"
                                            icon="truck" size="sm" square />
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.companies.show', $company->id) }}" wire:navigate
                                               class="font-medium text-signal-700 hover:underline">
                                                {{ $company->displayName() }}
                                            </a>
                                            <p class="text-2xs text-ink-500">{{ $company->contact_person }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="tnum text-ink-600" dir="ltr">{{ $company->phone }}</td>
                                <td class="tnum text-end">
                                    <span class="font-medium text-emerald-700">{{ $company->online_riders_count }}</span>
                                    <span class="text-ink-400">/{{ $company->riders_count }}</span>
                                </td>
                                <td class="tnum text-end">{{ $company->deliveries_count }}</td>
                                <td class="tnum text-end">
                                    {{ number_format($company->acceptanceRate() * 100, 0) }}%
                                </td>
                                <td class="tnum text-end">
                                    {{ number_format($company->completionRate() * 100, 0) }}%
                                </td>
                                <td>
                                    <x-ui.badge :tone="$company->status->tone()" dot>
                                        {{ $company->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="text-end">
                                    <x-ui.button variant="ghost" size="sm"
                                                 wire:click="toggleStatus('{{ $company->id }}')"
                                                 wire:confirm="{{ __('app.common.confirm') }}">
                                        {{ $company->status->value === 'active'
                                            ? __('audit.action.suspended')
                                            : __('audit.action.reinstated') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
