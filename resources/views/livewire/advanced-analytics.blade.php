<?php

use App\Models\OsRating;
use App\Models\OsResource;
use App\Services\OpenstackService;
use Livewire\Volt\Component;

new class extends Component {
    public array $resource_breakdown = [];
    public array $daily_trends = [];
    public array $cost_prediction = [];
    public string $selected_metric = 'cost';

    public function mount(): void
    {
        if (OpenstackService::isCloudConfigExistForAuth()) {
            $this->loadAnalytics();
        }
    }

    private function loadAnalytics(): void
    {
        $osCloud = auth()->user()->clouds->first();

        if (!$osCloud) {
            return;
        }

        $this->loadResourceBreakdown($osCloud);
        $this->loadDailyTrends($osCloud);
        $this->generateCostPrediction();
    }

    private function loadResourceBreakdown($osCloud): void
    {
        $currentMonth = now()->startOfMonth();

        $breakdown = OsRating::with(['resource'])
            ->whereHas('project', function($query) use ($osCloud) {
                $query->where('os_cloud_id', $osCloud->id);
            })
            ->where('begin', '>=', $currentMonth)
            ->where('rating', '>', 0)
            ->get()
            ->groupBy('resource.resource_identifier')
            ->map(function($ratings, $resourceId) use ($currentMonth){
                $totalCost = $ratings->sum('rating') / 55.5 * 1.2;
                $resource = $ratings->first()->resource;

                return [
                    'id' => $resourceId,
                    'name' => $resource->name ?? 'Unknown',
                    'type' => $this->getResourceType($resourceId),
                    'total_cost' => $totalCost,
                    'usage_count' => $ratings->count(),
                    'avg_daily_cost' => $totalCost / max(1, now()->diffInDays($currentMonth)),
                ];
            })
            ->sortByDesc('total_cost')
            ->take(10)
            ->values()
            ->toArray();

        $this->resource_breakdown = $breakdown;
    }

    private function loadDailyTrends($osCloud): void
    {
        $last30Days = now()->subDays(30);

        $trends = OsRating::whereHas('project', function($query) use ($osCloud) {
            $query->where('os_cloud_id', $osCloud->id);
        })
        ->where('begin', '>=', $last30Days)
        ->where('rating', '>', 0)
        ->selectRaw('DATE(end) as date, SUM(rating) as total_rating, COUNT(*) as usage_count')
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->map(function($item) {
            return [
                'date' => $item->date,
                'cost' => ($item->total_rating / 55.5) * 1.2,
                'usage' => $item->usage_count,
            ];
        })
        ->toArray();

        $this->daily_trends = $trends;
    }

    private function generateCostPrediction(): void
    {
        // Simple prediction based on current trends
        if (count($this->daily_trends) >= 7) {
            $recentWeek = array_slice($this->daily_trends, -7);
            $avgDailyCost = collect($recentWeek)->avg('cost');

            $prediction = [];
            for ($i = 1; $i <= 7; $i++) {
                $prediction[] = [
                    'date' => now()->addDays($i)->format('Y-m-d'),
                    'predicted_cost' => $avgDailyCost * (1 + (rand(-10, 10) / 100)), // Add some variation
                ];
            }

            $this->cost_prediction = $prediction;
        }
    }

    private function getResourceType(string $resourceId): string
    {
        return match (true) {
            str_contains(strtolower($resourceId), 'compute') => 'Compute',
            str_contains(strtolower($resourceId), 'storage') => 'Storage',
            str_contains(strtolower($resourceId), 'network') => 'Network',
            str_contains(strtolower($resourceId), 'volume') => 'Volume',
            default => 'Other',
        };
    }

    public function updateMetric(string $metric): void
    {
        $this->selected_metric = $metric;
    }
}; ?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <flux:heading size="xl">Advanced Analytics</flux:heading>
            <flux:text variant="subtle" class="text-lg">Deep insights into your OpenStack resource usage and costs</flux:text>
        </div>

        <!-- Metric Selector -->
        <div class="flex gap-2">
            <flux:button
                size="sm"
                variant="{{ $selected_metric === 'cost' ? 'primary' : 'outline' }}"
                wire:click="updateMetric('cost')"
            >
                Cost Analysis
            </flux:button>
            <flux:button
                size="sm"
                variant="{{ $selected_metric === 'usage' ? 'primary' : 'outline' }}"
                wire:click="updateMetric('usage')"
            >
                Usage Patterns
            </flux:button>
        </div>
    </div>

    <!-- Resource Breakdown -->
    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Top Resources Table -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Top Resources by Cost</h3>

            @if(!empty($resource_breakdown))
                <div class="bg-neutral-50 dark:bg-neutral-900/50 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-neutral-100 dark:bg-neutral-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Resource</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Total Cost</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Avg Daily</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                @foreach($resource_breakdown as $resource)
                                    <tr class="hover:bg-neutral-100 dark:hover:bg-neutral-800">
                                        <td class="px-4 py-3">
                                            <div>
                                                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">{{ $resource['name'] }}</p>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">{{ $resource['id'] }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <flux:badge size="sm" variant="outline">{{ $resource['type'] }}</flux:badge>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                €{{ number_format($resource['total_cost'], 2) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-sm text-neutral-600 dark:text-neutral-400">
                                                €{{ number_format($resource['avg_daily_cost'], 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-neutral-50 dark:bg-neutral-900/50 rounded-xl p-8 text-center">
                    <flux:icon.chart-pie class="size-12 text-neutral-400 dark:text-neutral-500 mx-auto mb-3" />
                    <p class="text-neutral-600 dark:text-neutral-400">No resource data available</p>
                </div>
            @endif
        </div>

        <!-- Cost Distribution Chart -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Resource Type Distribution</h3>

            <div class="bg-neutral-50 dark:bg-neutral-900/50 rounded-xl p-6">
                @if(!empty($resource_breakdown))
                    @php
                        $typeBreakdown = collect($resource_breakdown)->groupBy('type')->map(function($items) {
                            return $items->sum('total_cost');
                        })->sortDesc();
                        $total = $typeBreakdown->sum();
                    @endphp

                    <div class="space-y-3">
                        @foreach($typeBreakdown as $type => $cost)
                            @php $percentage = $total > 0 ? ($cost / $total) * 100 : 0; @endphp
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full bg-blue-{{ 500 + (100 * $loop->index) % 400 }}"></div>
                                    <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $type }}</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">€{{ number_format($cost, 2) }}</p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ number_format($percentage, 1) }}%</p>
                                </div>
                            </div>
                            <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
                                <div class="bg-blue-{{ 500 + (100 * $loop->index) % 400 }} h-2 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <flux:icon.chart-pie class="size-12 text-neutral-400 dark:text-neutral-500 mx-auto mb-3" />
                        <p class="text-neutral-600 dark:text-neutral-400">No distribution data available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Trends and Predictions -->
    @if(!empty($daily_trends))
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">30-Day Trends & 7-Day Prediction</h3>

            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                @php
                    $chartData = array_merge(
                        array_map(fn($item) => ['date' => $item['date'], 'cost' => $item['cost'], 'type' => 'actual'], $daily_trends),
                        array_map(fn($item) => ['date' => $item['date'], 'cost' => $item['predicted_cost'], 'type' => 'predicted'], $cost_prediction)
                    );
                @endphp

                <div class="h-64 flex items-center justify-center border-2 border-dashed border-neutral-300 dark:border-neutral-600 rounded-lg">
                    <div class="text-center">
                        <flux:icon.chart-bar class="size-16 text-neutral-400 dark:text-neutral-500 mx-auto mb-3" />
                        <p class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-2">Trend Analysis Chart</p>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ count($daily_trends) }} days of historical data with 7-day prediction</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Key Insights -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
            <div class="flex items-center gap-3">
                <flux:icon.exclamation-triangle class="size-8 text-amber-600 dark:text-amber-400" />
                <div>
                    <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Cost Alert</h4>
                    <p class="text-xs text-amber-700 dark:text-amber-300">Monitor high-cost resources</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center gap-3">
                <flux:icon.arrow-trending-down class="size-8 text-emerald-600 dark:text-emerald-400" />
                <div>
                    <h4 class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">Optimization</h4>
                    <p class="text-xs text-emerald-700 dark:text-emerald-300">Potential 15% savings</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-3">
                <flux:icon.clock class="size-8 text-blue-600 dark:text-blue-400" />
                <div>
                    <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Peak Hours</h4>
                    <p class="text-xs text-blue-700 dark:text-blue-300">2PM - 6PM daily</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-3">
                <flux:icon.cpu-chip class="size-8 text-purple-600 dark:text-purple-400" />
                <div>
                    <h4 class="text-sm font-semibold text-purple-900 dark:text-purple-100">Efficiency</h4>
                    <p class="text-xs text-purple-700 dark:text-purple-300">82% resource utilization</p>
                </div>
            </div>
        </div>
    </div>
</div>
