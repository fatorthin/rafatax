<x-filament-panels::page>
    <div 
        x-data="{
            tableEl: null,
            scrollWidth: 0,
            hasOverflow: false,
            init() {
                this.$nextTick(() => this.syncScrollbar());
                Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => {
                        this.$nextTick(() => this.syncScrollbar());
                    });
                });
                window.addEventListener('resize', () => this.syncScrollbar());
            },
            syncScrollbar() {
                const container = this.$refs.tableWrapper.querySelector('.fi-ta-content, .fi-ta-table-container, [class*=\'overflow-x-auto\']');
                if (!container) return;
                this.tableEl = container;
                this.scrollWidth = container.scrollWidth;
                this.hasOverflow = container.scrollWidth > container.clientWidth;

                container.onscroll = () => {
                    if (this.$refs.topScroll && Math.abs(this.$refs.topScroll.scrollLeft - container.scrollLeft) > 1) {
                        this.$refs.topScroll.scrollLeft = container.scrollLeft;
                    }
                };

                if (window.ResizeObserver && !this.observer) {
                    this.observer = new ResizeObserver(() => {
                        if (this.tableEl) {
                            this.scrollWidth = this.tableEl.scrollWidth;
                            this.hasOverflow = this.tableEl.scrollWidth > this.tableEl.clientWidth;
                        }
                    });
                    this.observer.observe(container);
                    const table = container.querySelector('table');
                    if (table) this.observer.observe(table);
                }
            },
            onTopScroll(e) {
                if (this.tableEl && Math.abs(this.tableEl.scrollLeft - e.target.scrollLeft) > 1) {
                    this.tableEl.scrollLeft = e.target.scrollLeft;
                }
            }
        }" 
        class="space-y-2"
    >
        <!-- Bilah Scrollbar Horizontal Atas -->
        <div 
            x-ref="topScroll"
            @scroll="onTopScroll"
            x-show="hasOverflow"
            x-cloak
            class="overflow-x-auto overflow-y-hidden rounded-xl bg-gray-100/90 dark:bg-gray-800/80 h-3.5 border border-gray-200/90 dark:border-gray-700 shadow-inner transition-all"
            style="scrollbar-width: thin;"
            title="Geser scroll horizontal tabel"
        >
            <div :style="'width: ' + scrollWidth + 'px; height: 1px;'"></div>
        </div>

        <!-- Container Tabel Filament -->
        <div x-ref="tableWrapper">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
