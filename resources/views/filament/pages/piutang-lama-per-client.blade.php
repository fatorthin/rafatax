<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Saldo Awal (2025) -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Saldo Awal (2025)
                </div>
                <div class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    Rp {{ number_format($stats['total_saldo_awal'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-400">Periode Tahun 2025</div>
            </div>
        </div>

        <!-- Card 2: Total Invoice -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Invoice Sebelum 2026
                </div>
                <div class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    Rp {{ number_format($stats['total_invoice'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-400">Invoice sebelum 2026</div>
            </div>
        </div>

        <!-- Card 3: Total Pembayaran / CoA 180 -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Pelunasan / CoA AO-103.5
                </div>
                <div class="text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($stats['total_pembayaran'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-400">Termasuk penerimaan CoA 180</div>
            </div>
        </div>

        <!-- Card 4: Total Sisa Piutang Lama -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Sisa Piutang Lama
                </div>
                <div class="text-2xl font-semibold tracking-tight {{ $stats['total_piutang'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-950 dark:text-white' }}">
                    Rp {{ number_format($stats['total_piutang'], 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-400">Sisa saldo piutang sebelum 2026</div>
            </div>
        </div>
    </div>

    <!-- Table with Top Horizontal Scrollbar -->
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
            class="overflow-x-auto overflow-y-hidden rounded-xl bg-gray-100/90 dark:bg-gray-800/80 h-3.5 border border-gray-200/90 dark:border-gray-700 shadow-inner transition-all no-print"
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
