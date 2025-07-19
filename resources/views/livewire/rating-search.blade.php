<?php

use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[Validate('string')]
    public string $begin_at;

    #[Validate('string')]
    public string $end_at;

    public string $time_diff;

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
    }

    public function mount(): void
    {
        $this->begin_at = now()->startOfMonth()->toIso8601String();
        $this->end_at = now()->subHour()->toIso8601String();
    }

    public function rendering(): void
    {
        $this->validate();

        $begin_at = Carbon::parse($this->begin_at);
        $end_at = Carbon::parse($this->end_at);

        $this->begin_at = $begin_at->startOfHour()->toIso8601String();
        $this->end_at = $end_at->startOfHour()->toIso8601String();

        $this->time_diff = $begin_at->diff($end_at)->forHumans();
    }
}; ?>

<section>
    <flux:heading size="lg" class="flex items-center gap-1">
        @lang('Choisissez la fourchette de date que vous souhaitez')

        <flux:tooltip toggleable="">
            <flux:button icon="information-circle" size="sm" variant="ghost" />

            <flux:tooltip.content class="max-w-[20rem] space-y-2">
                <p>@lang('Vous pouvez entrer une date avec une heure précise.')</p>
                <p>@lang('Format standard :') <i>YYYY-mm-dd HH:ii</i></p>
                <p>@lang('Il est aussi possible de saisir la date en langage naturel (en anglais uniquement).')</p>
                <p>@lang('Par exemple :') <i>first day of next month at 14:30</i></p>
            </flux:tooltip.content>
        </flux:tooltip>
    </flux:heading>
    <flux:text variant="subtle" wire:text="time_diff" class="mb-5"/>

    <div class="grid grid-cols-2 gap-x-4 gap-y-6">
        <flux:input
                wire:model.blur="begin_at"
                error="begin_at"
                type="text"
                label="{{ __('A partir de :') }}"
                placeholder="{{ now()->startOfMonth()->format('Y-m-d') }}"
                icon="calendar-date-range"
        />

        <flux:input
                wire:model.blur="end_at"
                error="end_at"
                type="text"
                label="{{ __('Jusqu\'à :') }}"
                placeholder="{{ now()->subHour()->format('Y-m-d') }}"
                icon="calendar-date-range"
        />
    </div>
</section>
