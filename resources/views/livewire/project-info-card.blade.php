<?php

use App\Services\OpenstackService;
use Livewire\Volt\Component;

new class extends Component {
    public array $project_info = [];
    public bool $cloud_connected = false;

    public function mount(): void
    {
        $this->cloud_connected = OpenstackService::isCloudConfigExistForAuth();

        if ($this->cloud_connected) {
            $this->loadProjectInfo();
        }
    }

    private function loadProjectInfo(): void
    {
        $osCloud = auth()->user()->clouds->first();

        if (!$osCloud) {
            return;
        }

        $this->project_info = [
            'cloud_name' => $osCloud->name ?? 'Default Cloud',
            'endpoint' => $osCloud->endpoint ?? 'N/A',
            'region' => $osCloud->region ?? 'N/A',
            'created_at' => $osCloud->created_at?->format('M j, Y') ?? 'N/A',
            'projects_count' => $osCloud->osProjects()->count(),
            'last_sync' => $osCloud->updated_at?->diffForHumans() ?? 'Never',
        ];
    }

    public function refreshConnection()
    {
        $this->dispatch('refresh-stats');
        session()->flash('message', 'Connection refreshed!');
    }
}; ?>

<div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
            Project Information
        </h3>
        <flux:badge
            variant="{{ $cloud_connected ? 'positive' : 'danger' }}"
            size="sm"
        >
            {{ $cloud_connected ? 'Connected' : 'Disconnected' }}
        </flux:badge>
    </div>

    @if($cloud_connected && !empty($project_info))
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-3">
                <div class="flex justify-between items-center py-2 border-b border-neutral-100 dark:border-neutral-700">
                    <span class="text-sm text-neutral-600 dark:text-neutral-400">Cloud Name</span>
                    <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $project_info['cloud_name'] }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-b border-neutral-100 dark:border-neutral-700">
                    <span class="text-sm text-neutral-600 dark:text-neutral-400">Region</span>
                    <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $project_info['region'] }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-b border-neutral-100 dark:border-neutral-700">
                    <span class="text-sm text-neutral-600 dark:text-neutral-400">Projects</span>
                    <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $project_info['projects_count'] }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-b border-neutral-100 dark:border-neutral-700">
                    <span class="text-sm text-neutral-600 dark:text-neutral-400">Last Sync</span>
                    <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $project_info['last_sync'] }}</span>
                </div>

                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-neutral-600 dark:text-neutral-400">Created</span>
                    <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $project_info['created_at'] }}</span>
                </div>
            </div>

            <div class="pt-4 border-t border-neutral-100 dark:border-neutral-700">
                <flux:button
                    size="sm"
                    variant="outline"
                    class="w-full"
                    wire:click="refreshConnection"
                >
                    <flux:icon.arrow-path class="size-4" />
                    Refresh Connection
                </flux:button>
            </div>
        </div>
    @else
        <div class="text-center py-6">
            <flux:icon.cloud class="size-12 text-neutral-400 dark:text-neutral-500 mx-auto mb-3" />
            <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">
                No OpenStack cloud connection configured.
            </p>
            <flux:button
                size="sm"
                :href="route('settings.openstack-cloud')"
                wire:navigate
            >
                <flux:icon.cog class="size-4" />
                Configure Cloud
            </flux:button>
        </div>
    @endif

    @if (session()->has('message'))
        <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('message') }}</p>
        </div>
    @endif
</div>
