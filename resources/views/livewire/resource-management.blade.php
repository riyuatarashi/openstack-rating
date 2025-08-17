<?php

use App\Models\OsResource;
use App\Models\OsRating;
use App\Services\OpenstackService;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filter_type = 'all';
    public string $sort_by = 'cost';
    public string $sort_direction = 'desc';
    public array $selected_resources = [];

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sort_by === $field) {
            $this->sort_direction = $this->sort_direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort_by = $field;
            $this->sort_direction = 'desc';
        }
        $this->resetPage();
    }

    public function selectAll(): void
    {
        $resources = $this->getResourcesQuery()->get();
        $this->selected_resources = $resources->pluck('id')->toArray();
    }

    public function deselectAll(): void
    {
        $this->selected_resources = [];
    }

    private function getResourcesQuery()
    {
        if (!OpenstackService::isCloudConfigExistForAuth()) {
            return collect([]);
        }

        $osCloud = auth()->user()->clouds->first();
        if (!$osCloud) {
            return collect([]);
        }

        $query = OsResource::whereHas('ratings', function($ratingQuery) use ($osCloud) {
            $ratingQuery->whereHas('project', function($projectQuery) use ($osCloud) {
                $projectQuery->where('os_cloud_id', $osCloud->id);
            });
        })->with(['ratings' => function($ratingQuery) use ($osCloud) {
            $ratingQuery->whereHas('project', function($projectQuery) use ($osCloud) {
                $projectQuery->where('os_cloud_id', $osCloud->id);
            })->where('begin', '>=', now()->startOfMonth());
        }]);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('resource_identifier', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filter_type !== 'all') {
            $query->where('resource_identifier', 'like', '%' . $this->filter_type . '%');
        }

        return $query;
    }

    public function with(): array
    {
        $resources = $this->getResourcesQuery()
            ->get()
            ->map(function($resource) {
                $totalCost = $resource->ratings->sum('rating') / 55.5 * 1.2;
                $avgDailyCost = $totalCost / max(1, now()->diffInDays(now()->startOfMonth()));

                return [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'identifier' => $resource->resource_identifier,
                    'type' => $this->getResourceType($resource->resource_identifier),
                    'total_cost' => $totalCost,
                    'avg_daily_cost' => $avgDailyCost,
                    'usage_count' => $resource->ratings->count(),
                    'last_activity' => $resource->ratings->max('end')?->diffForHumans() ?? 'N/A',
                    'status' => $resource->ratings->isNotEmpty() ? 'Active' : 'Inactive',
                ];
            })
            ->sortBy($this->sort_by, SORT_REGULAR, $this->sort_direction === 'desc')
            ->values();

        return [
            'resources' => $resources->forPage($this->getPage(), 10),
            'total_resources' => $resources->count(),
        ];
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

    public function exportSelected(): void
    {
        if (empty($this->selected_resources)) {
            session()->flash('error', 'Please select resources to export.');
            return;
        }

        session()->flash('message', 'Export functionality will be implemented soon.');
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <flux:heading size="xl">Resource Management</flux:heading>
            <flux:text variant="subtle" class="text-lg">Monitor and manage your OpenStack resources</flux:text>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-2">
            @if(count($selected_resources) > 0)
                <flux:button size="sm" variant="outline" wire:click="exportSelected">
                    <flux:icon.arrow-down-tray class="size-4" />
                    Export ({{ count($selected_resources) }})
                </flux:button>
                <flux:button size="sm" variant="ghost" wire:click="deselectAll">
                    Clear Selection
                </flux:button>
            @endif
            <flux:button size="sm">
                <flux:icon.arrow-path class="size-4" />
                Refresh
            </flux:button>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="grid lg:grid-cols-4 gap-4">
        <div class="lg:col-span-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search resources by name or identifier..."
                icon="magnifying-glass"
            />
        </div>

        <div>
            <flux:select wire:model.live="filter_type">
                <flux:select.option value="all">All Types</flux:select.option>
                <flux:select.option value="compute">Compute</flux:select.option>
                <flux:select.option value="storage">Storage</flux:select.option>
                <flux:select.option value="network">Network</flux:select.option>
                <flux:select.option value="volume">Volume</flux:select.option>
            </flux:select>
        </div>

        <div>
            <flux:select wire:model.live="sort_by">
                <flux:select.option value="cost">Sort by Cost</flux:select.option>
                <flux:select.option value="name">Sort by Name</flux:select.option>
                <flux:select.option value="usage_count">Sort by Usage</flux:select.option>
                <flux:select.option value="last_activity">Sort by Activity</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Resource Statistics -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-3">
                <flux:icon.server class="size-8 text-blue-600 dark:text-blue-400" />
                <div>
                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $total_resources }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">Total Resources</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
            <div class="flex items-center gap-3">
                <flux:icon.play class="size-8 text-green-600 dark:text-green-400" />
                <div>
                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                        {{ collect($resources)->where('status', 'Active')->count() }}
                    </p>
                    <p class="text-sm text-green-700 dark:text-green-300">Active Resources</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
            <div class="flex items-center gap-3">
                <flux:icon.currency-euro class="size-8 text-amber-600 dark:text-amber-400" />
                <div>
                    <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
                        €{{ number_format(collect($resources)->sum('total_cost'), 2) }}
                    </p>
                    <p class="text-sm text-amber-700 dark:text-amber-300">Total Monthly Cost</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-3">
                <flux:icon.check-circle class="size-8 text-purple-600 dark:text-purple-400" />
                <div>
                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ count($selected_resources) }}</p>
                    <p class="text-sm text-purple-700 dark:text-purple-300">Selected Resources</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Resources Table -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
        @if(!empty($resources) && count($resources) > 0)
            <flux:checkbox.group wire:model.live="selected_resources">
            <!-- Table Header -->
            <div class="bg-neutral-50 dark:bg-neutral-900/50 px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <flux:checkbox.all />
                        <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                            {{ count($selected_resources) > 0 ? count($selected_resources) . ' selected' : 'Select all' }}
                        </span>
                    </div>

                    <flux:text variant="subtle" class="text-sm">
                        Showing {{ count($resources) }} of {{ $total_resources }} resources
                    </flux:text>
                </div>
            </div>

            <!-- Table Body -->
            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @foreach($resources as $resource)
                    <div class="flex items-center gap-4 px-6 py-4 hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                        <flux:checkbox value="{{ $resource['id'] }}" />

                        <div class="flex-1 grid lg:grid-cols-6 gap-4 items-center">
                            <!-- Resource Info -->
                            <div class="lg:col-span-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-neutral-100 dark:bg-neutral-700 flex items-center justify-center">
                                        @if($resource['type'] === 'Compute')
                                            <flux:icon.cpu-chip class="size-5 text-neutral-600 dark:text-neutral-400" />
                                        @elseif($resource['type'] === 'Storage')
                                            <flux:icon.circle-stack class="size-5 text-neutral-600 dark:text-neutral-400" />
                                        @elseif($resource['type'] === 'Network')
                                            <flux:icon.signal class="size-5 text-neutral-600 dark:text-neutral-400" />
                                        @else
                                            <flux:icon.cube class="size-5 text-neutral-600 dark:text-neutral-400" />
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                                            {{ $resource['name'] }}
                                        </p>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">
                                            {{ $resource['identifier'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Type -->
                            <div>
                                <flux:badge variant="outline" size="sm">{{ $resource['type'] }}</flux:badge>
                            </div>

                            <!-- Status -->
                            <div>
                                <flux:badge
                                    variant="{{ $resource['status'] === 'Active' ? 'positive' : 'neutral' }}"
                                    size="sm"
                                >
                                    {{ $resource['status'] }}
                                </flux:badge>
                            </div>

                            <!-- Cost -->
                            <div class="text-right">
                                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    €{{ number_format($resource['total_cost'], 2) }}
                                </p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                    €{{ number_format($resource['avg_daily_cost'], 2) }}/day
                                </p>
                            </div>

                            <!-- Last Activity -->
                            <div class="text-right">
                                <p class="text-sm text-neutral-900 dark:text-neutral-100">
                                    {{ $resource['usage_count'] }} events
                                </p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ $resource['last_activity'] }}
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <flux:button size="xs" variant="ghost">
                                <flux:icon.eye class="size-4" />
                            </flux:button>
                            <flux:button size="xs" variant="ghost">
                                <flux:icon.cog class="size-4" />
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination (simplified for demo) -->
            <div class="bg-neutral-50 dark:bg-neutral-900/50 px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center justify-between">
                    <flux:text variant="subtle" class="text-sm">
                        Page 1 of {{ ceil($total_resources / 10) }}
                    </flux:text>
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="outline" disabled>
                            <flux:icon.chevron-left class="size-4" />
                            Previous
                        </flux:button>
                        <flux:button size="sm" variant="outline">
                            Next
                            <flux:icon.chevron-right class="size-4" />
                        </flux:button>
                    </div>
                </div>
            </div>
            </flux:checkbox.group>
        @else
            <!-- Empty State -->
            <div class="px-6 py-16 text-center">
                <flux:icon.server class="size-16 text-neutral-400 dark:text-neutral-500 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-2">No Resources Found</h3>
                <p class="text-neutral-600 dark:text-neutral-400 mb-6">
                    @if($search)
                        No resources match your search criteria. Try adjusting your search terms or filters.
                    @else
                        No resources are available for management at this time.
                    @endif
                </p>
                @if($search)
                    <flux:button size="sm" variant="outline" wire:click="$set('search', '')">
                        Clear Search
                    </flux:button>
                @endif
            </div>
        @endif
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('message') }}</p>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif
</div>
