<?php

use App\Models\OsRating;
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
    public array $resources;

    private int $color_index = 0;
    private array $colors = [
        'orange',
        'amber',
        'lime',
        'emerald',
        'cyan',
        'blue',
        'indigo',
        'purple',
        'fuchsia',
        'pink',
        'rose',
    ];

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        $this->openstackService = app(OpenstackService::class);
        shuffle($this->colors);
    }

    public function mount(): void
    {
        $this->date_range = new DateRange(now()->startOfMonth(), now()->subDay()->startOfDay());
    }

    public function rendering(): void
    {
        $this->time_diff = $this->date_range->start()->diff($this->date_range->end())->forHumans();

        if (OpenstackService::isCloudConfigExistForAuth()) {
            $ratings = [];

            /** @var \App\Models\OpenstackCloud $osCloud */
            $osCloud = auth()->user()->openstackClouds->first();

            /** @var \App\Models\OsProject $osProject */
            $osProject = $osCloud->osProjects->first();

            $osProject
                ->ratings()
                ->where('begin', '>=', $this->date_range->start())
                ->where('end', '<=', $this->date_range->end()->endOfDay())
                ->where('rating', '>', 0)
                ->chunkById(500, function ($osRatings) use (&$ratings) {
                    /** @var OsRating $osRating */
                    foreach ($osRatings as $osRating) {
                        $date = $osRating->end->startOfHour()->format('Y-m-d');

                        if (! isset($ratings[$date])) {
                            $ratings[$date] = [
                                'date'  => $date,
                            ];
                        }

                        if (! isset($ratings[$date][$osRating->resource->resource_identifier])) {
                            $ratings[$date][$osRating->resource->resource_identifier] = 0;
                            $this->resources[$osRating->resource->resource_identifier] = [
                                'name' => $osRating->resource->name,
                                'color' => $this->colors[$this->color_index++ % count($this->colors)],
                            ];
                        }

                        $ratings[$date][$osRating->resource->resource_identifier] += ($osRating->rating / 55.5) * 1.2;
                    }
                });

            ksort($ratings);

            $this->data = array_values($ratings);
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
            @foreach($this->resources as $id => $resource)
                <flux:chart.line field="{{ $id }}" class="
                    text-{{ $resource['color'] }}-500
                    dark:text-{{ $resource['color'] }}-400
                " />
            @endforeach

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
            <flux:chart.tooltip.heading field="date" :format="['dateStyle' => 'full']" />

            @foreach($this->resources as $id => $resource)
                <flux:chart.tooltip.value field="{{ $id }}">
                    <div class="
                        text-{{ $resource['color'] }}-500
                        dark:text-{{ $resource['color'] }}-400
                    ">
                        {{ $resource['name'] }}
                    </div>
                </flux:chart.tooltip.value>
            @endforeach
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
