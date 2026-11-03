<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="space-y-6">
        <!-- Stat Cards Row -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Card 1: Total Tagihan Invoice -->
            <div class="fi-wi-stats-overview-stat relative rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Tagihan (Invoice)</span>
                    <span class="p-2 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                        <i class="fa-solid fa-file-invoice text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Rp {{ number_format($stats['total_invoice'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-gray-400">Akumulasi seluruh kategori</div>
            </div>

            <!-- Card 2: Total Pembayaran -->
            <div class="fi-wi-stats-overview-stat relative rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Pembayaran Masuk</span>
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <i class="fa-solid fa-money-bill-wave text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($stats['total_pembayaran'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-emerald-600/80 dark:text-emerald-400/80 font-medium">
                    {{ $stats['rasio_pelunasan'] }}% terbayar
                </div>
            </div>

            <!-- Card 3: Total Sisa Piutang -->
            <div class="fi-wi-stats-overview-stat relative rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Sisa Piutang</span>
                    <span class="p-2 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                        <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight {{ $stats['total_piutang'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-950 dark:text-white' }}">
                    Rp {{ number_format($stats['total_piutang'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-amber-600/80 dark:text-amber-400/80 font-medium">
                    {{ $stats['count_kategori_piutang'] }} kategori ada piutang
                </div>
            </div>

            <!-- Card 4: Kategori Piutang Terbesar -->
            <div class="fi-wi-stats-overview-stat relative rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Piutang Terbesar</span>
                    <span class="p-2 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                        <i class="fa-solid fa-ranking-star text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-lg font-bold tracking-tight text-gray-950 dark:text-white truncate" title="{{ $stats['top_piutang_category'] }}">
                    {{ $stats['top_piutang_category'] }}
                </div>
                <div class="mt-1 text-xs text-purple-600 dark:text-purple-400 font-semibold">
                    Rp {{ number_format($stats['top_piutang_amount'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Table Container with Horizontal Scroll Sync -->
        <div x-data="{
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
        }" class="space-y-2">
            <!-- Top Horizontal Scrollbar -->
            <div x-ref="topScroll" @scroll="onTopScroll" x-show="hasOverflow" x-cloak
                class="overflow-x-auto overflow-y-hidden rounded-xl bg-gray-100/90 dark:bg-gray-800/80 h-3.5 border border-gray-200/90 dark:border-gray-700 shadow-inner transition-all no-print"
                style="scrollbar-width: thin;" title="Geser scroll horizontal tabel">
                <div :style="'width: ' + scrollWidth + 'px; height: 1px;'"></div>
            </div>

            <!-- Filament Table Wrapper -->
            <div x-ref="tableWrapper">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>

