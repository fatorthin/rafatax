<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Piutang: {{ $category->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        };
    </script>

    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        // Init Theme
        (function() {
            try {
                const savedTheme = localStorage.getItem('theme') || localStorage.getItem('cashReferenceTheme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = savedTheme || (prefersDark ? 'dark' : 'light');

                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .dark body {
            background-color: #0b0f19;
            color: #f3f4f6;
        }

        [x-cloak] {
            display: none !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white !important;
                color: black !important;
            }
            .print-card {
                border: 1px solid #e5e7eb !important;
                box-shadow: none !important;
                background-color: transparent !important;
                color: black !important;
            }
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 min-h-screen py-8 px-4 sm:px-6 lg:px-8 dark:bg-slate-950 dark:text-slate-100" x-data="{ activeTab: 'invoices', searchInvoice: '', searchPayment: '', searchMou: '' }">
    <div class="max-w-7xl mx-auto space-y-6">

        @php
            $user = auth()->user();
            $referer = request()->header('referer', '');
            $isAppPanel = str_contains($referer, '/app/');
            $isAdminRoute = !$isAppPanel && (str_contains($referer, '/admin/') || request()->is('admin/*') || ($user && $user->hasAnyRole(['admin', 'super_admin'])));
            $backRoute = $isAdminRoute ? '/admin/piutang-per-category-mou' : '/app/piutang-per-category-mou';
            $currentYear = (int) date('Y');
            $yearsList = range($currentYear, 2024);
        @endphp

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5 dark:border-slate-800 no-print">
            <div class="flex items-center gap-4">
                <a href="{{ $backRoute }}" class="inline-flex items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition shadow-sm dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white" title="Kembali ke Rekapitulasi">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <nav class="flex text-xs text-slate-500 mb-1 gap-1.5 items-center dark:text-slate-400">
                        <span>Keuangan</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <a href="{{ $backRoute }}" class="hover:underline">Piutang per Kategori MoU</a>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <span class="text-slate-800 font-medium dark:text-slate-200">Rincian Kategori</span>
                    </nav>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                        <span>{{ $category->name }}</span>
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800">
                            ID: {{ $category->id }}
                        </span>
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <!-- Export Excel -->
                <a href="{{ route('piutang-category-mou.export-detail', ['id' => $category->id, 'year' => $year, 'entity' => $entity, 'status' => $status]) }}"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition shadow-sm shadow-emerald-600/20">
                    <i class="fa-solid fa-file-excel text-sm"></i>
                    <span>Export Excel</span>
                </a>

                <!-- Print -->
                <button onclick="window.print()" type="button"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition shadow-sm dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak</span>
                </button>

                <!-- Theme Toggle -->
                <button type="button" onclick="toggleTheme()" class="inline-flex items-center justify-center p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                    <i class="fa-solid fa-moon dark:hidden text-sm"></i>
                    <i class="fa-solid fa-sun hidden dark:block text-sm text-amber-400"></i>
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 no-print">
            <form method="GET" action="{{ route('piutang-category-mou.detail', $category->id) }}" class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-slate-400">
                        <i class="fa-solid fa-filter mr-1"></i> Filter:
                    </span>

                    <!-- Filter Tahun -->
                    <div class="flex items-center gap-1.5">
                        <label for="year" class="text-xs text-slate-600 dark:text-slate-400 font-medium">Tahun:</label>
                        <select name="year" id="year" onchange="this.form.submit()" class="text-xs rounded-xl border-slate-200 bg-slate-50 py-1.5 px-3 font-medium text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                            @foreach ($yearsList as $y)
                                <option value="{{ $y }}" {{ (string) $year === (string) $y ? 'selected' : '' }}>{{ $y }} (Tahun Ini)</option>
                            @endforeach
                            <option value="all" {{ $year === 'all' ? 'selected' : '' }}>Semua Waktu</option>
                        </select>
                    </div>

                    <!-- Filter Tipe Entitas -->
                    <div class="flex items-center gap-1.5">
                        <label for="entity" class="text-xs text-slate-600 dark:text-slate-400 font-medium">Tipe MoU:</label>
                        <select name="entity" id="entity" onchange="this.form.submit()" class="text-xs rounded-xl border-slate-200 bg-slate-50 py-1.5 px-3 font-medium text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                            <option value="all" {{ $entity === 'all' ? 'selected' : '' }}>Semua (PT & KKP)</option>
                            <option value="pt" {{ $entity === 'pt' ? 'selected' : '' }}>Hanya PT</option>
                            <option value="kkp" {{ $entity === 'kkp' ? 'selected' : '' }}>Hanya KKP</option>
                        </select>
                    </div>

                    <!-- Filter Status Pembayaran -->
                    <div class="flex items-center gap-1.5">
                        <label for="status" class="text-xs text-slate-600 dark:text-slate-400 font-medium">Status Tagihan:</label>
                        <select name="status" id="status" onchange="this.form.submit()" class="text-xs rounded-xl border-slate-200 bg-slate-50 py-1.5 px-3 font-medium text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                            <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Hanya Belum Lunas (Piutang)</option>
                            <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Hanya Lunas</option>
                        </select>
                    </div>
                </div>

                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span>Menampilkan <strong>{{ $countTotal }}</strong> invoice dari <strong>{{ $countMou }}</strong> MoU</span>
                </div>
            </form>
        </div>

        <!-- 4 Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Tagihan -->
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Tagihan (Invoice)</span>
                    <span class="p-2 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                        <i class="fa-solid fa-file-invoice text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Rp {{ number_format($totalTagihan, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>{{ $countTotal }} total invoice</span>
                    <span>{{ $countMou }} MoU terkait</span>
                </div>
            </div>

            <!-- Total Pembayaran -->
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Pembayaran Diterima</span>
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <i class="fa-solid fa-money-bill-wave text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($totalTerbayar, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ $countPaid }} invoice lunas</span>
                </div>
            </div>

            <!-- Sisa Piutang -->
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sisa Saldo Piutang</span>
                    <span class="p-2 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                        <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight {{ $totalSisaPiutang > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }}">
                    Rp {{ number_format($totalSisaPiutang, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ $countUnpaid }} invoice belum lunas</span>
                </div>
            </div>

            <!-- Rasio Pelunasan -->
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rasio Pelunasan</span>
                    <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                        <i class="fa-solid fa-chart-pie text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400">
                    {{ $rasioPelunasan }}%
                </div>
                <div class="mt-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, $rasioPelunasan) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Tab Controls & Search -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
            <!-- Tab Headers -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 px-6 pt-4 gap-4 no-print">
                <div class="flex items-center gap-6 overflow-x-auto">
                    <button type="button" @click="activeTab = 'invoices'"
                        :class="activeTab === 'invoices' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 font-medium'"
                        class="pb-3 border-b-2 text-sm flex items-center gap-2 transition">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Rincian Invoice</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ count($invoices) }}</span>
                    </button>

                    <button type="button" @click="activeTab = 'payments'"
                        :class="activeTab === 'payments' ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 font-medium'"
                        class="pb-3 border-b-2 text-sm flex items-center gap-2 transition">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Riwayat Pembayaran</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ count($payments) }}</span>
                    </button>

                    <button type="button" @click="activeTab = 'mous'"
                        :class="activeTab === 'mous' ? 'border-sky-600 text-sky-600 dark:border-sky-400 dark:text-sky-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 font-medium'"
                        class="pb-3 border-b-2 text-sm flex items-center gap-2 transition">
                        <i class="fa-solid fa-folder-tree"></i>
                        <span>Rincian per MoU</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ count($mou_breakdown) }}</span>
                    </button>
                </div>

                <!-- Tab Search Input -->
                <div class="pb-3">
                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input x-show="activeTab === 'invoices'" x-model="searchInvoice" type="text" placeholder="Cari invoice / klien / mou..."
                            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                        <input x-show="activeTab === 'payments'" x-model="searchPayment" type="text" placeholder="Cari pembayaran / klien..."
                            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                        <input x-show="activeTab === 'mous'" x-model="searchMou" type="text" placeholder="Cari nomor MoU / klien..."
                            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- TAB 1: RINCIAN INVOICE                         -->
            <!-- ============================================== -->
            <div x-show="activeTab === 'invoices'" class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 text-slate-700 uppercase tracking-wider text-[11px] font-semibold border-b border-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 text-center w-12">No</th>
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">No. Invoice</th>
                            <th class="py-3 px-4">Klien / Perusahaan</th>
                            <th class="py-3 px-4">No. MoU</th>
                            <th class="py-3 px-4 text-center">Tipe</th>
                            <th class="py-3 px-4 text-right">Nominal Tagihan</th>
                            <th class="py-3 px-4 text-center">Status Pembayaran</th>
                            <th class="py-3 px-4 text-center">Status Kirim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($invoices as $idx => $inv)
                            <tr x-show="!searchInvoice || '{{ strtolower($inv['invoice_number'] . ' ' . $inv['client_name'] . ' ' . $inv['mou_number']) }}'.includes(searchInvoice.toLowerCase())"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 text-center text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    {{ $inv['invoice_date'] ? \Carbon\Carbon::parse($inv['invoice_date'])->format('d M Y') : '-' }}
                                    @if ($inv['due_date'])
                                        <div class="text-[10px] text-slate-400">Jatuh tempo: {{ \Carbon\Carbon::parse($inv['due_date'])->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    {{ $inv['invoice_number'] }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-700 dark:text-slate-300">
                                    {{ $inv['client_name'] }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ $inv['mou_number'] }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ strtolower($inv['invoice_type']) === 'pt' ? 'bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800' : 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800' }}">
                                        {{ strtoupper($inv['invoice_type']) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                    Rp {{ number_format($inv['amount'], 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if ($inv['invoice_status'] === 'paid')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                            <i class="fa-solid fa-check text-[10px]"></i> Lunas
                                        </span>
                                        @if ($inv['tgl_transfer'])
                                            <div class="text-[10px] text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($inv['tgl_transfer'])->format('d/m/Y') }}</div>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">
                                            <i class="fa-solid fa-clock text-[10px]"></i> Belum Lunas
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if ($inv['is_send_invoice'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                                            Terkirim
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            Belum
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 dark:text-slate-600 block"></i>
                                    Tidak ada data invoice untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($invoices) > 0)
                        <tfoot class="bg-slate-50 font-semibold border-t border-slate-200 text-slate-800 dark:bg-slate-800/80 dark:border-slate-700 dark:text-slate-200">
                            <tr>
                                <td colspan="6" class="py-3 px-4 text-center">TOTAL KESELURUHAN</td>
                                <td class="py-3 px-4 text-right text-slate-900 dark:text-white">
                                    Rp {{ number_format($totalTagihan, 0, ',', '.') }}
                                </td>
                                <td colspan="2" class="py-3 px-4 text-center text-xs text-slate-500">
                                    Terbayar: Rp {{ number_format($totalTerbayar, 0, ',', '.') }} | Piutang: Rp {{ number_format($totalSisaPiutang, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- ============================================== -->
            <!-- TAB 2: RIWAYAT PEMBAYARAN                      -->
            <!-- ============================================== -->
            <div x-show="activeTab === 'payments'" class="overflow-x-auto" x-cloak>
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 text-slate-700 uppercase tracking-wider text-[11px] font-semibold border-b border-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 text-center w-12">No</th>
                            <th class="py-3 px-4">Tanggal Pembayaran</th>
                            <th class="py-3 px-4">No. Invoice</th>
                            <th class="py-3 px-4">Klien / Perusahaan</th>
                            <th class="py-3 px-4">No. MoU</th>
                            <th class="py-3 px-4 text-center">Rekening Kas / Bank Penerima</th>
                            <th class="py-3 px-4 text-right">Nominal Diterima</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($payments as $idx => $pay)
                            <tr x-show="!searchPayment || '{{ strtolower($pay['invoice_number'] . ' ' . $pay['client_name'] . ' ' . $pay['mou_number'] . ' ' . $pay['rek_transfer']) }}'.includes(searchPayment.toLowerCase())"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 text-center text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    {{ $pay['date'] ? \Carbon\Carbon::parse($pay['date'])->format('d M Y') : '-' }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    {{ $pay['invoice_number'] }}
                                </td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                                    {{ $pay['client_name'] }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ $pay['mou_number'] }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                        <i class="fa-solid fa-building-columns mr-1 text-[10px]"></i> {{ $pay['rek_transfer'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    Rp {{ number_format($pay['amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300 dark:text-slate-600 block"></i>
                                    Belum ada data pembayaran yang tercatat pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($payments) > 0)
                        <tfoot class="bg-slate-50 font-semibold border-t border-slate-200 text-slate-800 dark:bg-slate-800/80 dark:border-slate-700 dark:text-slate-200">
                            <tr>
                                <td colspan="6" class="py-3 px-4 text-center">TOTAL PEMBAYARAN DITERIMA</td>
                                <td class="py-3 px-4 text-right text-emerald-600 dark:text-emerald-400">
                                    Rp {{ number_format($totalTerbayar, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- ============================================== -->
            <!-- TAB 3: BREAKDOWN PER MOU                       -->
            <!-- ============================================== -->
            <div x-show="activeTab === 'mous'" class="overflow-x-auto" x-cloak>
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 text-slate-700 uppercase tracking-wider text-[11px] font-semibold border-b border-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 text-center w-12">No</th>
                            <th class="py-3 px-4">No. MoU</th>
                            <th class="py-3 px-4">Klien / Perusahaan</th>
                            <th class="py-3 px-4 text-center">Tipe</th>
                            <th class="py-3 px-4 text-right">Nilai Kontrak MoU</th>
                            <th class="py-3 px-4 text-right">Total Invoice</th>
                            <th class="py-3 px-4 text-right">Terbayar</th>
                            <th class="py-3 px-4 text-right">Sisa Piutang</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($mou_breakdown as $idx => $mou)
                            <tr x-show="!searchMou || '{{ strtolower($mou['mou_number'] . ' ' . $mou['client_name']) }}'.includes(searchMou.toLowerCase())"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 text-center text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    {{ $mou['mou_number'] }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-700 dark:text-slate-300">
                                    {{ $mou['client_name'] }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ strtolower($mou['type']) === 'pt' ? 'bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300' : 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300' }}">
                                        {{ strtoupper($mou['type']) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    Rp {{ number_format($mou['cost_mou'], 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    Rp {{ number_format($mou['total_invoice'], 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    Rp {{ number_format($mou['total_paid'], 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold whitespace-nowrap {{ $mou['sisa_piutang'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500' }}">
                                    Rp {{ number_format($mou['sisa_piutang'], 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if ($mou['sisa_piutang'] <= 0 && $mou['total_invoice'] > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400">
                                            Lunas
                                        </span>
                                    @elseif ($mou['total_invoice'] == 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            Belum Tagih
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400">
                                            Piutang
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300 dark:text-slate-600 block"></i>
                                    Tidak ada data MoU untuk kategori ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Script Theme Toggle -->
    <script>
        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>
</body>

</html>

