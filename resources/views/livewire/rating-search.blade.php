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
    public array $resources = [];

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

            /** @var \App\Models\OsCloud $osCloud */
            $osCloud = auth()->user()->clouds->first();

            /** @var \App\Models\OsProject $osProject */
            $osProject = $osCloud->osProjects->first();

            if (! $osProject) {
                $this->data = [];
                return;
            }

            $osProject
                ->ratings()
                ->with('resource')
                ->where('begin', '>=', $this->date_range->start())
                ->where('end', '<=', $this->date_range->end()->endOfDay())
                ->where('rating', '>', 0)
                ->chunkById(500, function ($osRatings) use (&$ratings): void {
                    /** @var OsRating $osRating */
                    foreach ($osRatings as $osRating) {
                        $date = $osRating->end->startOfHour()->format('Y-m-d');

                        if (! isset($ratings[$date])) {
                            $ratings[$date] = [
                                'date' => $date,
                            ];
                        }

                        if (! isset($ratings[$date][$osRating->resource->resource_identifier])) {
                            $ratings[$date][$osRating->resource->resource_identifier] = 0;
                            $this->resources[$osRating->resource->resource_identifier] = [
                                'name'  => $osRating->resource->name,
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
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="text-neutral-900 dark:text-neutral-100">
                Cost Analytics
            </flux:heading>
            <flux:text variant="subtle" wire:text="time_diff" class="text-lg" />
        </div>
        
        <!-- Quick Date Filters -->
        <div class="flex flex-wrap gap-2">
            <flux:button 
                size="sm" 
                variant="outline"
                wire:click="$set('date_range', {{ json_encode(new Flux\DateRange(now()->startOfMonth(), now()->subDay()->startOfDay())) }})"
            >
                This Month
            </flux:button>
            <flux:button 
                size="sm" 
                variant="outline"
                wire:click="$set('date_range', {{ json_encode(new Flux\DateRange(now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth())) }})"
            >
                Last Month
            </flux:button>
            <flux:button 
                size="sm" 
                variant="outline"
                wire:click="$set('date_range', {{ json_encode(new Flux\DateRange(now()->subDays(7), now()->subDay()->startOfDay())) }})"
            >
                Last 7 Days
            </flux:button>
        </div>
    </div>

    <!-- Calendar Section -->
    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <!-- Calendar -->
        <div class="lg:col-span-1">
            <div class="bg-neutral-50 dark:bg-neutral-900/50 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-4">Select Date Range</h3>
                <flux:calendar
                    :selectable-header="true"
                    size="sm"
                    mode="range"
                    max="{{ now()->subDay()->format('Y-m-d') }}"
                    wire:model.change="date_range"
                    class="w-full"
                />
            </div>
        </div>
        
        <!-- Resource Legend -->
        <div class="lg:col-span-2">
            <div class="bg-neutral-50 dark:bg-neutral-900/50 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-4">Resources</h3>
                @if(!empty($this->resources))
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($this->resources as $id => $resource)
                            <div class="flex items-center gap-3 p-2 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <div class="w-4 h-4 rounded-full bg-{{ $resource['color'] }}-500"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">{{ $resource['name'] }}</p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ $id }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-neutral-500 dark:text-neutral-400">
                        <flux:icon.cube class="size-8 mx-auto mb-2" />
                        <p class="text-sm">No resources found for the selected period</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="mb-8">
        @if(!empty($data))
            <flux:chart wire:model="data" class="aspect-[2/1] bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
                <flux:chart.svg>
                    @foreach($this->resources as $id => $resource)
                        <flux:chart.line field="{{ $id }}" class="
                            text-{{ $resource['color'] }}-500
                            dark:text-{{ $resource['color'] }}-400
                            stroke-2
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
                                flex items-center gap-2
                            ">
                                <div class="w-3 h-3 rounded-full bg-{{ $resource['color'] }}-500"></div>
                                {{ $resource['name'] }}
                            </div>
                        </flux:chart.tooltip.value>
                    @endforeach
                </flux:chart.tooltip>
            </flux:chart>
        @else
            <div class="aspect-[2/1] bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-8">
                <div class="h-full flex flex-col items-center justify-center text-neutral-500 dark:text-neutral-400">
                    <flux:icon.chart-bar class="size-16 mb-4" />
                    <h3 class="text-lg font-medium mb-2">No Data Available</h3>
                    <p class="text-sm text-center">Select a date range with billing data to view the cost analytics chart.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                    <flux:icon.currency-euro class="size-5 text-white" />
                </div>
                <div>
                    <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100">Total Cost</h3>
                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                        €{{ number_format(collect($data)->sum(function($item) { return array_sum(array_filter($item, 'is_numeric')); }), 2) }}
                    </p>
                </div>
            </div>
            <p class="text-sm text-blue-700 dark:text-blue-300">For selected period</p>
        </div>
        
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                    <flux:icon.cube class="size-5 text-white" />
                </div>
                <div>
                    <h3 class="text-sm font-medium text-green-900 dark:text-green-100">Resources</h3>
                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ count($this->resources) }}</p>
                </div>
            </div>
            <p class="text-sm text-green-700 dark:text-green-300">Active billing resources</p>
        </div>
        
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                    <flux:icon.calendar-days class="size-5 text-white" />
                </div>
                <div>
                    <h3 class="text-sm font-medium text-purple-900 dark:text-purple-100">Days</h3>
                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ count($data) }}</p>
                </div>
            </div>
            <p class="text-sm text-purple-700 dark:text-purple-300">Data points collected</p>
        </div>
    </div>
</section>
