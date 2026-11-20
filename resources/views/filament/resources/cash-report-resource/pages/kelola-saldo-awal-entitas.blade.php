<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Panel Kontrol & Pemilihan Entitas --}}
        <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Pemisahan Saldo Awal Entitas</span>
                        <x-filament::badge :color="$entity === 'PT' ? 'primary' : 'success'" size="sm">
                            Entitas: {{ $entity }}
                        </x-filament::badge>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Posisi per: <strong>{{ $this->getCutoffDateLabel() }}</strong> (Neraca Awal Periode April {{ $year }})
                    </p>
                </div>

                {{-- Switcher Entitas --}}
                <div class="flex items-center gap-2 p-1.5 bg-gray-100 dark:bg-gray-900 rounded-lg">
                    <x-filament::button
                        type="button"
                        wire:click="changeEntity('PT')"
                        :color="$entity === 'PT' ? 'primary' : 'gray'"
                        :outlined="$entity !== 'PT'"
                        size="sm"
                        icon="heroicon-m-building-office-2"
                    >
                        Entitas PT
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        wire:click="changeEntity('KKP')"
                        :color="$entity === 'KKP' ? 'success' : 'gray'"
                        :outlined="$entity !== 'KKP'"
                        size="sm"
                        icon="heroicon-m-building-library"
                    >
                        Entitas KKP
                    </x-filament::button>
                </div>
            </div>

            {{-- Toolbar Tombol Pembantu --}}
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-filament::button
                        type="button"
                        wire:click="salinDariKonsolidasi"
                        wire:confirm="Apakah Anda yakin ingin menyalin seluruh nilai dari Saldo Konsolidasi Maret {{ $year }} ke formulir {{ $entity }}?"
                        color="warning"
                        :outlined="true"
                        size="xs"
                        icon="heroicon-m-clipboard-document-check"
                    >
                        Salin dari Saldo Konsolidasi
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        wire:click="kosongkanSemua"
                        wire:confirm="Apakah Anda yakin ingin mengosongkan seluruh nilai input untuk {{ $entity }}?"
                        color="danger"
                        :outlined="true"
                        size="xs"
                        icon="heroicon-m-trash"
                    >
                        Kosongkan Semua Nilai
                    </x-filament::button>
                </div>

                <div class="flex items-center gap-3">
                    <input type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari kode / nama akun..."
                        class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        {{-- Sticky Ringkasan Keseimbangan (Realtime Balance Card) --}}
        @php
            $selisihNominal = abs($this->selisih);
            // Toleransi selisih pecahan rupiah/sen (<= Rp 1.0)
            $isBalance = $selisihNominal <= 1.0;
        @endphp
        <div class="sticky top-16 z-20 p-4 rounded-xl border shadow-md transition-all {{ $isBalance ? 'bg-emerald-50/90 border-emerald-300 dark:bg-emerald-950/60 dark:border-emerald-700' : 'bg-rose-50/90 border-rose-300 dark:bg-rose-950/60 dark:border-rose-700' }}"
            style="background-color: {{ $isBalance ? '#f0fdf4' : '#fff1f2' }}; border: 1px solid {{ $isBalance ? '#86efac' : '#fca5a5' }};">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex flex-wrap items-center gap-6">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">Total Debit ({{ $entity }})</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($this->totalDebit, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-gray-300 dark:text-gray-600 font-light text-2xl hidden md:block">|</div>
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">Total Kredit ({{ $entity }})</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($this->totalCredit, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-gray-300 dark:text-gray-600 font-light text-2xl hidden md:block">|</div>
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">Status Keseimbangan</div>
                        <div class="flex items-center gap-2 mt-0.5">
                            @if ($isBalance)
                                <x-filament::badge color="success" size="lg" icon="heroicon-m-check-circle">
                                    SEIMBANG (BALANCE)
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="danger" size="lg" icon="heroicon-m-exclamation-triangle">
                                    SELISIH: Rp {{ number_format($selisihNominal, 0, ',', '.') }}
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tombol Simpan --}}
                <div class="flex flex-col items-end gap-1">
                    <x-filament::button
                        type="button"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        :color="$isBalance ? ($entity === 'KKP' ? 'success' : 'primary') : 'gray'"
                        size="lg"
                        icon="heroicon-m-check-badge"
                        :disabled="!$isBalance"
                        class="shadow-sm"
                    >
                        <span wire:loading.remove wire:target="save">Simpan Saldo Awal {{ $entity }}</span>
                        <span wire:loading wire:target="save">Menyimpan Data...</span>
                    </x-filament::button>
                    @if (!$isBalance)
                        <span class="text-[11px] text-rose-600 dark:text-rose-400 font-medium">Seimbangkan Debit & Kredit sebelum menyimpan</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabel Input Saldo Awal Akun --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 text-gray-700 uppercase font-semibold text-xs border-b border-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 w-32">Kode Akun</th>
                            <th class="px-4 py-3">Nama Akun & Grup</th>
                            <th class="px-4 py-3 text-right bg-gray-100/70 dark:bg-gray-800 w-44">Konsolidasi (Debit)</th>
                            <th class="px-4 py-3 text-right bg-gray-100/70 dark:bg-gray-800 w-44">Konsolidasi (Kredit)</th>
                            <th class="px-4 py-3 text-right bg-blue-50/70 dark:bg-blue-950/30 w-48 font-bold text-blue-900 dark:text-blue-200">
                                Saldo {{ $entity }} (Debit)
                            </th>
                            <th class="px-4 py-3 text-right bg-blue-50/70 dark:bg-blue-950/30 w-48 font-bold text-blue-900 dark:text-blue-200">
                                Saldo {{ $entity }} (Kredit)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @php
                            $currentGroup = null;
                        @endphp
                        @forelse ($items as $coaId => $item)
                            @if (!empty($search) && (!str_contains(strtolower($item['code']), strtolower($search)) && !str_contains(strtolower($item['name']), strtolower($search))))
                                @continue
                            @endif

                            {{-- Group Separator --}}
                            @if ($currentGroup !== $item['group_name'])
                                @php
                                    $currentGroup = $item['group_name'];
                                @endphp
                                <tr class="bg-gray-100 dark:bg-gray-900 font-bold text-xs uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    <td colspan="6" class="px-4 py-2 border-l-4 border-blue-600">
                                        {{ $currentGroup }}
                                    </td>
                                </tr>
                            @endif

                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-2.5 font-mono font-medium text-gray-900 dark:text-white">
                                    {{ $item['code'] }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $item['group_name'] }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-500 bg-gray-50/50 dark:bg-gray-800/50">
                                    {{ $item['ref_debit'] > 0 ? number_format($item['ref_debit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-500 bg-gray-50/50 dark:bg-gray-800/50">
                                    {{ $item['ref_credit'] > 0 ? number_format($item['ref_credit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-3 py-1.5 text-right bg-blue-50/30 dark:bg-blue-950/10">
                                    <input type="number"
                                        step="0.01"
                                        wire:model.live.debounce.500ms="items.{{ $coaId }}.debit"
                                        class="w-full text-right font-mono text-sm px-2.5 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                </td>
                                <td class="px-3 py-1.5 text-right bg-blue-50/30 dark:bg-blue-950/10">
                                    <input type="number"
                                        step="0.01"
                                        wire:model.live.debounce.500ms="items.{{ $coaId }}.credit"
                                        class="w-full text-right font-mono text-sm px-2.5 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                    Tidak ada akun neraca yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100/80 dark:bg-gray-900 border-t-2 border-gray-300 dark:border-gray-700 font-bold text-xs">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-gray-900 dark:text-white uppercase tracking-wider">
                                Total Keseluruhan
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-700 dark:text-gray-300">
                                Rp {{ number_format(collect($items)->sum('ref_debit'), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-700 dark:text-gray-300">
                                Rp {{ number_format(collect($items)->sum('ref_credit'), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-blue-900 dark:text-blue-300 bg-blue-50/50 dark:bg-blue-950/20">
                                Rp {{ number_format($this->totalDebit, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-blue-900 dark:text-blue-300 bg-blue-50/50 dark:bg-blue-950/20">
                                Rp {{ number_format($this->totalCredit, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Bottom Action Bar --}}
        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-medium">Status Akhir:</span>
                @if ($isBalance)
                    <x-filament::badge color="success" size="md" icon="heroicon-m-check-circle">
                        SEIMBANG (BALANCE)
                    </x-filament::badge>
                @else
                    <x-filament::badge color="danger" size="md" icon="heroicon-m-exclamation-triangle">
                        SELISIH: Rp {{ number_format($selisihNominal, 0, ',', '.') }}
                    </x-filament::badge>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Resources\CashReportResource::getUrl('neraca-lajur-piutang', ['year' => $year, 'month' => $month, 'entity' => strtolower($entity)])"
                    color="gray"
                    :outlined="true"
                    size="md"
                    icon="heroicon-m-arrow-left"
                >
                    Kembali
                </x-filament::button>

                <x-filament::button
                    type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    :color="$isBalance ? ($entity === 'KKP' ? 'success' : 'primary') : 'gray'"
                    size="md"
                    icon="heroicon-m-check-badge"
                    :disabled="!$isBalance"
                >
                    <span wire:loading.remove wire:target="save">Simpan Saldo Awal {{ $entity }}</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </x-filament::button>
            </div>
        </div>

    </div>
</x-filament-panels::page>

