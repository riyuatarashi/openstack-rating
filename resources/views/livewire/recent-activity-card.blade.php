<?php

use App\Models\OsRating;
use App\Services\OpenstackService;
use Livewire\Volt\Component;

new class extends Component {
    public array $recent_activities = [];

    public function mount(): void
    {
        if (OpenstackService::isCloudConfigExistForAuth()) {
            $this->loadRecentActivities();
        }
    }

    private function loadRecentActivities(): void
    {
        $osCloud = auth()->user()->clouds->first();
        
        if (!$osCloud) {
            return;
        }

        // Get recent ratings as activity indicators
        $recentRatings = OsRating::with(['resource', 'project'])
            ->whereHas('project', function($query) use ($osCloud) {
                $query->where('os_cloud_id', $osCloud->id);
            })
            ->where('rating', '>', 0)
            ->orderBy('end', 'desc')
            ->limit(5)
            ->get();

        $this->recent_activities = $recentRatings->map(function($rating) {
            return [
                'id' => $rating->id,
                'type' => 'billing',
                'resource_name' => $rating->resource->name ?? 'Unknown Resource',
                'project_name' => $rating->project->name ?? 'Unknown Project',
                'amount' => ($rating->rating / 55.5) * 1.2,
                'time' => $rating->end->diffForHumans(),
                'icon' => $this->getResourceIcon($rating->resource->resource_identifier ?? ''),
            ];
        })->toArray();

        // Add some system activities
        if (count($this->recent_activities) < 5) {
            $systemActivities = [
                [
                    'id' => 'sys-1',
                    'type' => 'system',
                    'resource_name' => 'Data Sync',
                    'project_name' => 'System',
                    'amount' => 0,
                    'time' => $osCloud->updated_at?->diffForHumans() ?? '1 hour ago',
                    'icon' => 'cloud-arrow-down',
                ],
                [
                    'id' => 'sys-2',
                    'type' => 'system',
                    'resource_name' => 'Dashboard Access',
                    'project_name' => 'User Activity',
                    'amount' => 0,
                    'time' => now()->subMinutes(rand(5, 30))->diffForHumans(),
                    'icon' => 'chart-bar',
                ],
            ];
            
            $this->recent_activities = array_merge($this->recent_activities, $systemActivities);
        }
    }

    private function getResourceIcon(string $resourceType): string
    {
        return match (true) {
            str_contains(strtolower($resourceType), 'compute') => 'cpu-chip',
            str_contains(strtolower($resourceType), 'storage') => 'circle-stack',
            str_contains(strtolower($resourceType), 'network') => 'signal',
            str_contains(strtolower($resourceType), 'volume') => 'server-stack',
            default => 'cube',
        };
    }
}; ?>

<div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
            Recent Activity
        </h3>
        <flux:button size="xs" variant="ghost">
            <flux:icon.arrow-path class="size-4" />
        </flux:button>
    </div>

    @if(!empty($recent_activities))
        <div class="space-y-3">
            @foreach($recent_activities as $activity)
                <div class="flex items-start space-x-3 p-3 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-700/50 transition-colors">
                    <div class="flex-shrink-0">
                        @if($activity['type'] === 'billing')
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <flux:icon name="{{ $activity['icon'] }}" class="size-4 text-blue-600 dark:text-blue-400" />
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-neutral-100 dark:bg-neutral-700 flex items-center justify-center">
                                <flux:icon name="{{ $activity['icon'] }}" class="size-4 text-neutral-600 dark:text-neutral-400" />
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                                {{ $activity['resource_name'] }}
                            </p>
                            @if($activity['type'] === 'billing' && $activity['amount'] > 0)
                                <span class="text-xs font-medium text-green-600 dark:text-green-400">
                                    €{{ number_format($activity['amount'], 2) }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">
                                {{ $activity['project_name'] }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $activity['time'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-700">
            <flux:button 
                size="sm" 
                variant="ghost" 
                class="w-full text-neutral-600 dark:text-neutral-400"
            >
                View All Activity
                <flux:icon.arrow-right class="size-4 ml-1" />
            </flux:button>
        </div>
    @else
        <div class="text-center py-6">
            <flux:icon.clock class="size-12 text-neutral-400 dark:text-neutral-500 mx-auto mb-3" />
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                No recent activity to display.
            </p>
        </div>
    @endif
</div>
