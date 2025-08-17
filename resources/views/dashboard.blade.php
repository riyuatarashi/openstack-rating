<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Project Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-700 dark:to-purple-700 rounded-xl p-6 text-white">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold">OpenStack Rating Dashboard</h1>
                    <p class="text-blue-100 dark:text-purple-100 text-lg">Monitor and analyze your OpenStack resource costs and usage patterns</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4">
                    <livewire:dashboard-stats />
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-neutral-200 dark:border-neutral-700 overflow-x-auto">
            <nav class="flex space-x-4 md:space-x-8 min-w-max px-4 md:px-0" aria-label="Tabs">
                <button 
                    class="dashboard-tab-active border-b-2 border-blue-500 py-3 px-2 text-sm font-medium text-blue-600 dark:text-blue-400 whitespace-nowrap" 
                    data-tab="overview"
                >
                    <span class="hidden sm:inline">Overview</span>
                    <span class="sm:hidden">📊</span>
                </button>
                <button 
                    class="dashboard-tab border-b-2 border-transparent py-3 px-2 text-sm font-medium text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-300 whitespace-nowrap" 
                    data-tab="analytics"
                >
                    <span class="hidden sm:inline">Analytics</span>
                    <span class="sm:hidden">📈</span>
                </button>
                <button 
                    class="dashboard-tab border-b-2 border-transparent py-3 px-2 text-sm font-medium text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-300 whitespace-nowrap" 
                    data-tab="resources"
                >
                    <span class="hidden sm:inline">Resources</span>
                    <span class="sm:hidden">🖥️</span>
                </button>
                <button 
                    class="dashboard-tab border-b-2 border-transparent py-3 px-2 text-sm font-medium text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-300 whitespace-nowrap" 
                    data-tab="reports"
                >
                    <span class="hidden sm:inline">Reports</span>
                    <span class="sm:hidden">📄</span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="flex-1 min-h-0">
            <!-- Overview Tab -->
            <div id="overview-tab" class="tab-content space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Chart -->
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                            <livewire:rating-search />
                        </div>
                    </div>
                    
                    <!-- Project Info -->
                    <div class="space-y-6">
                        <livewire:project-info-card />
                        <livewire:recent-activity-card />
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div id="analytics-tab" class="tab-content hidden">
                <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <livewire:advanced-analytics />
                </div>
            </div>

            <!-- Resources Tab -->
            <div id="resources-tab" class="tab-content hidden">
                <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <livewire:resource-management />
                </div>
            </div>

            <!-- Reports Tab -->
            <div id="reports-tab" class="tab-content hidden">
                <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <livewire:report-generator />
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Smooth animations for dashboard components */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        .scale-in {
            animation: scaleIn 0.2s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes scaleIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .tab-content {
            transition: all 0.3s ease-in-out;
        }
        
        .tab-content.hidden {
            opacity: 0;
            transform: translateY(10px);
        }
        
        .tab-content:not(.hidden) {
            opacity: 1;
            transform: translateY(0);
        }
        
        .dashboard-tab, .dashboard-tab-active {
            transition: all 0.2s ease-in-out;
        }
        
        /* Loading states */
        .loading {
            position: relative;
            overflow: hidden;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent
            );
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
    </style>

    <script>
        // Enhanced SPA-like tab switching with animations
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.dashboard-tab, .dashboard-tab-active');
            const tabContents = document.querySelectorAll('.tab-content');

            // Add initial animations
            document.querySelectorAll('.slide-up').forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active classes with transition
                    tabButtons.forEach(btn => {
                        btn.className = 'dashboard-tab border-b-2 border-transparent py-3 px-2 text-sm font-medium text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-300 whitespace-nowrap';
                    });
                    
                    // Add active class to clicked button
                    this.className = 'dashboard-tab-active border-b-2 border-blue-500 py-3 px-2 text-sm font-medium text-blue-600 dark:text-blue-400 whitespace-nowrap';
                    
                    // Hide all tab contents with fade out
                    tabContents.forEach(content => {
                        content.style.opacity = '0';
                        content.style.transform = 'translateY(10px)';
                        setTimeout(() => {
                            content.classList.add('hidden');
                        }, 150);
                    });
                    
                    // Show selected tab content with fade in
                    const selectedTab = document.getElementById(tabId + '-tab');
                    if (selectedTab) {
                        setTimeout(() => {
                            selectedTab.classList.remove('hidden');
                            selectedTab.style.opacity = '0';
                            selectedTab.style.transform = 'translateY(10px)';
                            
                            // Trigger fade in animation
                            requestAnimationFrame(() => {
                                selectedTab.style.transition = 'all 0.3s ease-out';
                                selectedTab.style.opacity = '1';
                                selectedTab.style.transform = 'translateY(0)';
                            });
                        }, 150);
                    }
                });
            });
            
            // Add hover effects to cards
            document.querySelectorAll('.bg-gradient-to-r, .bg-gradient-to-br').forEach(card => {
                card.style.transition = 'transform 0.2s ease-out, box-shadow 0.2s ease-out';
                
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.1)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });
            
            // Add loading states for dynamic content
            window.addEventListener('livewire:init', () => {
                Livewire.on('loading', () => {
                    document.querySelectorAll('[wire\\:loading]').forEach(el => {
                        el.classList.add('loading');
                    });
                });
                
                Livewire.on('loaded', () => {
                    document.querySelectorAll('.loading').forEach(el => {
                        el.classList.remove('loading');
                    });
                });
            });
        });
    </script>
</x-layouts.app>
