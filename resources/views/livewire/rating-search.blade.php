<?php

use App\Services\OpenstackService;
use Carbon\Carbon;
use Flux\DateRange;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[Session]
    public DateRange $date_range;
    public string $time_diff;
    public array $data;

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        $this->openstackService = app(OpenstackService::class);
    }

    public function mount(): void
    {
        $this->date_range = new DateRange(now()->startOfMonth(), now()->subDay()->startOfDay());
    }

    public function rendering(): void
    {
        $this->time_diff = $this->date_range->start()->diff($this->date_range->end())->forHumans();

        if (OpenstackService::isCloudConfigExistForAuth()) {
            $ratings = $this->openstackService->getRatingsFor($this->date_range);
            $this->data = $this->openstackService->parseRatingsToGetTotalByDay($ratings);
        }
    }
}; ?>

<section>
    <flux:heading size="lg" class="flex items-center gap-1">
        @lang('Choisissez la fourchette de date que vous souhaitez')
    </flux:heading>

    <flux:text variant="subtle" wire:text="time_diff" class="mb-5" />

    <div class="mt-5 mx-auto w-max">
        <flux:calendar
                :selectable-header="true"
                size="xs"
                mode="range"
                max="{{ now()->subDay()->format('Y-m-d') }}"
                wire:model.change="date_range"
        />
    </div>

    <flux:separator />

    <flux:chart wire:model="data" class="aspect-3/1 mt-5">
        <flux:chart.svg>
            <flux:chart.line field="total" class="text-pink-500 dark:text-pink-400" />

            <flux:chart.axis axis="x" field="date">
                <flux:chart.axis.line />
                <flux:chart.axis.tick />
            </flux:chart.axis>

            <flux:chart.axis axis="y" :format="['style' => 'currency', 'currency' => 'EUR']">
                <flux:chart.axis.grid />
                <flux:chart.axis.tick />
            </flux:chart.axis>

            <flux:chart.cursor />
        </flux:chart.svg>

        <flux:chart.tooltip>
            <flux:chart.tooltip.heading
                field="date"
                :format="[
                    'year' => 'numeric',
                    'month' => 'numeric',
                    'day' => 'numeric',
                    'hour' => '2-digit',
                    'minute' => '2-digit',
                    'second' => '2-digit'
                ]" />
            <flux:chart.tooltip.value field="total" label="Price" />
        </flux:chart.tooltip>
    </flux:chart>

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</section>
