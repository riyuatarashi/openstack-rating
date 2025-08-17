<?php

use App\Services\OpenstackService;
use Flux\DateRange;
use Livewire\Volt\Component;

new class extends Component {
    public string $report_type = 'cost_summary';
    public DateRange $date_range;
    public array $selected_resources = [];
    public string $format = 'pdf';
    public string $group_by = 'resource';
    public bool $include_charts = true;
    public bool $include_details = true;
    public array $available_resources = [];

    public function mount(): void
    {
        $this->date_range = new DateRange(now()->startOfMonth(), now()->endOfMonth());
        $this->loadAvailableResources();
    }

    private function loadAvailableResources(): void
    {
        if (!OpenstackService::isCloudConfigExistForAuth()) {
            return;
        }

        $osCloud = auth()->user()->clouds->first();
        if (!$osCloud) {
            return;
        }

        // Mock resource data for demo
        $this->available_resources = [
            ['id' => 1, 'name' => 'Web Server Instance', 'type' => 'Compute'],
            ['id' => 2, 'name' => 'Database Storage', 'type' => 'Storage'],
            ['id' => 3, 'name' => 'Load Balancer', 'type' => 'Network'],
            ['id' => 4, 'name' => 'Backup Volume', 'type' => 'Volume'],
            ['id' => 5, 'name' => 'API Server Instance', 'type' => 'Compute'],
        ];
    }

    public function toggleResource(int $resourceId): void
    {
        if (in_array($resourceId, $this->selected_resources)) {
            $this->selected_resources = array_filter($this->selected_resources, fn($id) => $id !== $resourceId);
        } else {
            $this->selected_resources[] = $resourceId;
        }
    }

    public function selectAllResources(): void
    {
        $this->selected_resources = collect($this->available_resources)->pluck('id')->toArray();
    }

    public function deselectAllResources(): void
    {
        $this->selected_resources = [];
    }

    public function generateReport(): void
    {
        // Validate inputs
        if (empty($this->selected_resources) && $this->report_type !== 'overview') {
            session()->flash('error', 'Please select at least one resource for the report.');
            return;
        }

        // Mock report generation
        $reportData = [
            'type' => $this->report_type,
            'date_range' => $this->date_range,
            'resources' => count($this->selected_resources),
            'format' => $this->format,
            'generated_at' => now()->format('M j, Y H:i'),
        ];

        session()->flash('message', 'Report generated successfully! Download will start shortly.');

        // Here you would implement actual report generation logic
        $this->dispatch('report-generated', $reportData);
    }

    public function previewReport(): void
    {
        session()->flash('message', 'Report preview functionality will be implemented soon.');
    }

    public function getReportTypes(): array
    {
        return [
            'overview' => 'Executive Overview',
            'cost_summary' => 'Cost Summary',
            'detailed_billing' => 'Detailed Billing',
            'resource_usage' => 'Resource Usage',
            'trend_analysis' => 'Trend Analysis',
            'cost_optimization' => 'Cost Optimization',
        ];
    }
}; ?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <flux:heading size="xl">Report Generator</flux:heading>
            <flux:text variant="subtle" class="text-lg">Create custom reports for your OpenStack usage and costs</flux:text>
        </div>

        <!-- Quick Actions -->
        <div class="flex gap-2">
            <flux:button size="sm" variant="outline" wire:click="previewReport">
                <flux:icon.eye class="size-4" />
                Preview
            </flux:button>
            <flux:button size="sm" wire:click="generateReport">
                <flux:icon.document-arrow-down class="size-4" />
                Generate Report
            </flux:button>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Configuration Panel -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Report Type -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Report Configuration</h3>

                <div class="space-y-4">
                    <!-- Report Type Selection -->
                    <div>
                        <flux:field>
                            <flux:label>Report Type</flux:label>
                            <flux:select wire:model.live="report_type">
                                @foreach($this->getReportTypes() as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <flux:field>
                            <flux:label>Date Range</flux:label>
                            <div class="mt-2">
                                <flux:calendar
                                    mode="range"
                                    size="sm"
                                    wire:model.change="date_range"
                                    max="{{ now()->format('Y-m-d') }}"
                                />
                            </div>
                        </flux:field>
                    </div>

                    <!-- Group By -->
                    <div>
                        <flux:field>
                            <flux:label>Group Data By</flux:label>
                            <flux:select wire:model.live="group_by">
                                <flux:select.option value="resource">Resource</flux:select.option>
                                <flux:select.option value="type">Resource Type</flux:select.option>
                                <flux:select.option value="project">Project</flux:select.option>
                                <flux:select.option value="date">Date</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>

                    <!-- Format Selection -->
                    <div>
                        <flux:field>
                            <flux:label>Export Format</flux:label>
                            <div class="flex gap-4 mt-2">
                                <flux:radio wire:model="format" value="pdf" label="PDF Report" />
                                <flux:radio wire:model="format" value="excel" label="Excel Spreadsheet" />
                                <flux:radio wire:model="format" value="csv" label="CSV Data" />
                            </div>
                        </flux:field>
                    </div>

                    <!-- Report Options -->
                    <div>
                        <flux:field>
                            <flux:label>Include Options</flux:label>
                            <div class="space-y-2 mt-2">
                                <flux:checkbox wire:model="include_charts" label="Include charts and visualizations" />
                                <flux:checkbox wire:model="include_details" label="Include detailed breakdowns" />
                            </div>
                        </flux:field>
                    </div>
                </div>
            </div>

            <!-- Resource Selection -->
            @if($report_type !== 'overview')
                <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Resource Selection</h3>
                        <div class="flex gap-2">
                            <flux:button size="xs" variant="ghost" wire:click="selectAllResources">
                                Select All
                            </flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deselectAllResources">
                                Clear All
                            </flux:button>
                        </div>
                    </div>

                    @if(!empty($available_resources))
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($available_resources as $resource)
                                <div class="flex items-center gap-3 p-3 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                                    <flux:checkbox @checked(in_array($resource['id'], $selected_resources))
                                        wire:click="toggleResource({{ $resource['id'] }})"
                                    />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            {{ $resource['name'] }}
                                        </p>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $resource['type'] }} Resource
                                        </p>
                                    </div>
                                    <flux:badge variant="outline" size="sm">{{ $resource['type'] }}</flux:badge>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 p-3 bg-neutral-50 dark:bg-neutral-900/50 rounded-lg">
                            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                                {{ count($selected_resources) }} of {{ count($available_resources) }} resources selected
                            </p>
                        </div>
                    @else
                        <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                            <flux:icon.server class="size-12 mx-auto mb-3" />
                            <p>No resources available for reporting</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Report Preview/Summary -->
        <div class="space-y-6">
            <!-- Report Summary -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Report Summary</h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-neutral-100 dark:border-neutral-700">
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Type</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            {{ $this->getReportTypes()[$report_type] ?? 'Unknown' }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-neutral-100 dark:border-neutral-700">
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Period</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            {{ $date_range->start()->format('M j') }} - {{ $date_range->end()->format('M j, Y') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-neutral-100 dark:border-neutral-700">
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Resources</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            @if($report_type === 'overview')
                                All Resources
                            @else
                                {{ count($selected_resources) }} selected
                            @endif
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-neutral-100 dark:border-neutral-700">
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Format</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            {{ strtoupper($format) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Group By</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            {{ ucfirst($group_by) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Recent Reports -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Recent Reports</h3>

                <div class="space-y-3">
                    <!-- Mock recent reports -->
                    @php
                        $recentReports = [
                            ['name' => 'Monthly Cost Summary', 'type' => 'PDF', 'date' => '2 hours ago', 'size' => '2.4 MB'],
                            ['name' => 'Resource Usage Report', 'type' => 'Excel', 'date' => '1 day ago', 'size' => '1.8 MB'],
                            ['name' => 'Quarterly Overview', 'type' => 'PDF', 'date' => '3 days ago', 'size' => '4.2 MB'],
                        ];
                    @endphp

                    @foreach($recentReports as $report)
                        <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                            <div class="w-8 h-8 rounded-lg bg-neutral-100 dark:bg-neutral-700 flex items-center justify-center">
                                <flux:icon.document-text class="size-4 text-neutral-600 dark:text-neutral-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                                    {{ $report['name'] }}
                                </p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ $report['type'] }} • {{ $report['size'] }} • {{ $report['date'] }}
                                </p>
                            </div>
                            <flux:button size="xs" variant="ghost">
                                <flux:icon.arrow-down-tray class="size-4" />
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Report Templates -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Quick Templates</h3>

                <div class="space-y-2">
                    <flux:button variant="outline" size="sm" class="w-full justify-start">
                        <flux:icon.chart-bar class="size-4" />
                        Monthly Executive Report
                    </flux:button>
                    <flux:button variant="outline" size="sm" class="w-full justify-start">
                        <flux:icon.currency-dollar class="size-4" />
                        Cost Optimization Analysis
                    </flux:button>
                    <flux:button variant="outline" size="sm" class="w-full justify-start">
                        <flux:icon.server class="size-4" />
                        Resource Utilization Report
                    </flux:button>
                    <flux:button variant="outline" size="sm" class="w-full justify-start">
                        <flux:icon.calendar class="size-4" />
                        Weekly Usage Summary
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <div class="flex items-center gap-3">
                <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400" />
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('message') }}</p>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-center gap-3">
                <flux:icon.exclamation-triangle class="size-5 text-red-600 dark:text-red-400" />
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('report-generated', (event) => {
            // Simulate file download
            console.log('Report generated:', event);

            // You could implement actual file download logic here
            // For demo purposes, we'll just show a notification
            setTimeout(() => {
                alert('Report download would start now! (Demo mode)');
            }, 1000);
        });
    });
</script>
