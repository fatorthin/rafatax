<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Panel Kontrol & Pemilihan Entitas --}}
        <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Pemisahan Saldo Awal Entitas</span>
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $entity === 'PT' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' }}">
                            Entitas: {{ $entity }}
                        </span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Posisi per: <strong>{{ $this->getCutoffDateLabel() }}</strong> (Neraca Awal Periode April {{ $year }})
                    </p>
                </div>

                {{-- Switcher Entitas --}}
                <div class="flex items-center gap-2 p-1.5 bg-gray-100 dark:bg-gray-900 rounded-lg">
                    <button type="button"
                        wire:click="changeEntity('PT')"
                        class="px-4 py-2 text-sm font-semibold rounded-md transition-all flex items-center gap-2 {{ $entity === 'PT' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Entitas PT</span>
                    </button>
                    <button type="button"
                        wire:click="changeEntity('KKP')"
                        class="px-4 py-2 text-sm font-semibold rounded-md transition-all flex items-center gap-2 {{ $entity === 'KKP' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                        <span>Entitas KKP</span>
                    </button>
                </div>
            </div>

            {{-- Toolbar Tombol Pembantu --}}
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button type="button"
                        wire:click="salinDariKonsolidasi"
                        wire:confirm="Apakah Anda yakin ingin menyalin seluruh nilai dari Saldo Konsolidasi Maret 2026 ke formulir {{ $entity }}?"
                        class="px-3 py-1.5 text-xs font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 dark:text-amber-300 dark:bg-amber-950/50 dark:hover:bg-amber-900 border border-amber-200 dark:border-amber-800 rounded-md transition">
                        📋 Salin dari Saldo Konsolidasi
                    </button>
                    <button type="button"
                        wire:click="kosongkanSemua"
                        wire:confirm="Apakah Anda yakin ingin mengosongkan seluruh nilai input untuk {{ $entity }}?"
                        class="px-3 py-1.5 text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 dark:text-rose-300 dark:bg-rose-950/50 dark:hover:bg-rose-900 border border-rose-200 dark:border-rose-800 rounded-md transition">
                        🗑️ Kosongkan Semua Nilai
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <input type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari kode / nama akun..."
                        class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        {{-- Sticky Ringkasan Keseimbangan (Realtime Balance Card) --}}
        @php
            $isBalance = abs($this->selisih) <= 0.01;
        @endphp
        <div class="sticky top-16 z-20 p-4 rounded-xl border shadow-md {{ $isBalance ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800' : 'bg-rose-50 border-rose-200 dark:bg-rose-950/40 dark:border-rose-800' }}">
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
                        <div class="flex items-center gap-2">
                            @if ($isBalance)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    SEIMBANG (BALANCE)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    SELISIH: Rp {{ number_format(abs($this->selisih), 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tombol Simpan --}}
                <div class="flex items-center gap-3">
                    <button type="button"
                        wire:click="save"
                        class="px-5 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm transition flex items-center gap-2 {{ $isBalance ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed opacity-75' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Simpan Saldo Awal {{ $entity }}</span>
                    </button>
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
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>

