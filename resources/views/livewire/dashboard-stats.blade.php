<?php

use App\Models\OsRating;
use App\Models\OsResource;
use App\Services\OpenstackService;
use Livewire\Volt\Component;

new class extends Component {
    public int $total_resources = 0;
    public float $monthly_cost = 0;
    public int $active_projects = 0;
    public string $cost_trend = 'stable';

    public function mount(): void
    {
        if (OpenstackService::isCloudConfigExistForAuth()) {
            $this->calculateStats();
        }
    }

    private function calculateStats(): void
    {
        $osCloud = auth()->user()->clouds->first();

        if (!$osCloud) {
            return;
        }

        // Count total resources
        $this->total_resources = OsResource::query()->whereHas('ratings', function($query) use ($osCloud) {
            $query->whereHas('project', function($projectQuery) use ($osCloud) {
                $projectQuery->where('os_cloud_id', $osCloud->id);
            });
        })->count();

        // Calculate monthly cost
        $currentMonth = now()->startOfMonth();
        $this->monthly_cost = OsRating::query()->whereHas('project', function($query) use ($osCloud) {
            $query->where('os_cloud_id', $osCloud->id);
        })
        ->where('begin', '>=', $currentMonth)
        ->sum('rating') / 55.5 * 1.2; // Same calculation as in rating-search

        // Count active projects
        $this->active_projects = $osCloud->osProjects()->count();

        // Determine cost trend (simplified)
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthCost = OsRating::whereHas('project', function($query) use ($osCloud) {
            $query->where('os_cloud_id', $osCloud->id);
        })
        ->where('begin', '>=', $lastMonth)
        ->where('begin', '<', $currentMonth)
        ->sum('rating') / 55.5 * 1.2;

        if ($this->monthly_cost > $lastMonthCost * 1.1) {
            $this->cost_trend = 'up';
        } elseif ($this->monthly_cost < $lastMonthCost * 0.9) {
            $this->cost_trend = 'down';
        }
    }
}; ?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
        <div class="text-2xl font-bold">{{ $total_resources }}</div>
        <div class="text-sm opacity-90">Active Resources</div>
    </div>

    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
        <div class="flex items-center justify-center gap-2">
            <span class="text-2xl font-bold">€{{ number_format($monthly_cost, 2) }}</span>
            @if($cost_trend === 'up')
                <flux:icon.arrow-trending-up class="size-5 text-red-200" />
            @elseif($cost_trend === 'down')
                <flux:icon.arrow-trending-down class="size-5 text-green-200" />
            @else
                <flux:icon.arrow-right class="size-5 text-white/70" />
            @endif
        </div>
        <div class="text-sm opacity-90">Monthly Cost</div>
    </div>

    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
        <div class="text-2xl font-bold">{{ $active_projects }}</div>
        <div class="text-sm opacity-90">Projects</div>
    </div>
</div>
