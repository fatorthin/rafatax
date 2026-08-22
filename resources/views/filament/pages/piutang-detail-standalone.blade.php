<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Piutang: [{{ $client->code }}] {{ $client->company_name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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
                        sans: ['Plus Jakarta Sans', 'Outfit', 'sans-serif'],
                    }
                }
            }
        };
    </script>
    
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

        /* Print styling optimization */
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
            .print-table {
                border: 1px solid #d1d5db !important;
            }
            .print-table th, .print-table td {
                border: 1px solid #e5e7eb !important;
            }
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 min-h-screen py-8 px-4 sm:px-6 lg:px-8 dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto space-y-8">
        
        @php
            $user = auth()->user();
            $referer = request()->header('referer', '');
            $isAdminRoute = str_contains($referer, '/admin/') || request()->is('admin/*') || ($user && $user->hasAnyRole(['admin', 'super_admin']));
            $backRoute = $isAdminRoute ? '/admin/piutang-per-client' : '/app/piutang-per-client';
        @endphp
        <!-- Header / Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 no-print">
            <div class="flex items-center gap-4">
                <a href="{{ $backRoute }}" class="inline-flex items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <nav class="flex text-xs text-slate-500 mb-1 gap-1.5 items-center dark:text-slate-400">
                        <span>Keuangan</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <a href="{{ $backRoute }}" class="hover:underline">Piutang per Client</a>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <span class="text-slate-800 font-medium dark:text-slate-200">Kartu Piutang</span>
                    </nav>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Kartu Piutang Client
                    </h1>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Theme Toggle -->
                <button onclick="toggleTheme()" class="inline-flex items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                
                <!-- Print Button -->
                <button onclick="window.print()" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-sm shadow-sm hover:bg-blue-700 hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <i class="fa-solid fa-print mr-2"></i> Print Kartu Piutang
                </button>
            </div>
        </div>

        <!-- Client Info Block (Always Printed) -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kode Client</span>
                    <span class="text-lg font-bold text-slate-900 dark:text-white mt-1 block">
                        {{ $client->code }}
                    </span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Nama Client</span>
                    <span class="text-lg font-bold text-slate-900 dark:text-white mt-1 block">
                        {{ $client->company_name }}
                    </span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Telepon</span>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 mt-1 block">
                        {{ $client->phone ?: '-' }}
                    </span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Alamat</span>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 mt-1 block truncate" title="{{ $client->address }}">
                        {{ $client->address ?: '-' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Period Tabs / Filter (No Print) -->
        <div class="flex flex-wrap items-center justify-between gap-4 no-print">
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <a href="{{ route('piutang-per-client.detail', ['id' => $client->id, 'periode' => 'all']) }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ ($periode ?? 'all') === 'all' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    <i class="fa-solid fa-layer-group mr-1.5 text-[11px]"></i> Semua Periode
                </a>
                <a href="{{ route('piutang-per-client.detail', ['id' => $client->id, 'periode' => 'pre_2025']) }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ ($periode ?? 'all') === 'pre_2025' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    <i class="fa-solid fa-clock-rotate-left mr-1.5 text-[11px]"></i> Sebelum 2025 (&lt; 2025)
                </a>
                <a href="{{ route('piutang-per-client.detail', ['id' => $client->id, 'periode' => 'post_2025']) }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all {{ ($periode ?? 'all') === 'post_2025' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    <i class="fa-regular fa-calendar mr-1.5 text-[11px]"></i> Tahun 2025 ke Atas (&ge; 2025)
                </a>
            </div>
            
            <div class="text-xs text-slate-500 dark:text-slate-400">
                @if(($periode ?? 'all') === 'pre_2025')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 font-medium">
                        <i class="fa-solid fa-circle-info"></i> Menampilkan piutang sebelum 2025 & pelunasan CoA AO-103.5
                    </span>
                @elseif(($periode ?? 'all') === 'post_2025')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 font-medium">
                        <i class="fa-solid fa-circle-info"></i> Menampilkan transaksi invoice & pembayaran periode 2025 ke atas
                    </span>
                @endif
            </div>
        </div>

        <!-- Summary Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Saldo Awal -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card relative">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Saldo Awal Piutang</span>
                    <div class="flex items-center gap-2">
                        <button onclick="openSaldoAwalModal()" class="no-print p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors" title="{{ $saldoAwalRecord ? 'Edit Saldo Awal' : 'Tambah Saldo Awal' }}">
                            <i class="fa-solid {{ $saldoAwalRecord ? 'fa-pen-to-square' : 'fa-plus' }} text-xs"></i>
                        </button>
                        <span class="p-2 rounded-xl bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <i class="fa-solid fa-wallet"></i>
                        </span>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white block">
                        Rp {{ number_format($saldoAwal, 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-slate-400 mt-1 block">Saldo piutang bawaan</span>
                </div>
            </div>

            <!-- Total Invoice -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Invoice (Debit)</span>
                    <span class="p-2 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white block">
                        Rp {{ number_format($totalInvoice, 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-amber-600 dark:text-amber-400 mt-1 block">Total tagihan baru (2026+)</span>
                </div>
            </div>

            <!-- Total Pembayaran -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Pembayaran (Kredit)</span>
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white block">
                        Rp {{ number_format($totalPembayaran, 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 block">Total dana diterima (2026+)</span>
                </div>
            </div>

            <!-- Diskon & Cancel MoU -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Diskon & Cancel MoU</span>
                    <span class="p-2 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/30 dark:text-purple-400">
                        <i class="fa-solid fa-tags"></i>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white block">
                        Rp {{ number_format($totalPotongan, 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-purple-600 dark:text-purple-400 mt-1 block">Total potongan piutang</span>
                </div>
            </div>

            <!-- Sisa Piutang -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Sisa Piutang</span>
                    @if($sisaPiutang > 0)
                        <span class="p-2 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>
                    @else
                        <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                    @endif
                </div>
                <div class="mt-4">
                    <span class="text-2xl font-bold block @if($sisaPiutang > 0) text-amber-600 dark:text-amber-400 @else text-emerald-600 dark:text-emerald-400 @endif">
                        Rp {{ number_format($sisaPiutang, 0, ',', '.') }}
                    </span>
                    <span class="text-xs mt-1 block @if($sisaPiutang > 0) text-rose-500 @else text-emerald-600 dark:text-emerald-400 @endif">
                        {{ $sisaPiutang > 0 ? 'Belum lunas sepenuhnya' : 'Lunas sepenuhnya' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- MoU List Table -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800 print-card">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-file-contract text-blue-600"></i> Daftar MoU Client
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 print-table">
                    <thead class="bg-slate-50/75 dark:bg-slate-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-16">No</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">No MoU</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Deskripsi MoU</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kategori MoU</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Cost List MoU</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Nominal Invoice</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nominal Discount</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nominal Cancel MoU</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-900 dark:divide-slate-800">
                        @php $mouNo = 1; @endphp
                        @forelse($mous as $mou)
                            @php
                                $totalCostList = $mou->cost_lists->sum('total_amount');
                                $totalInvoiceNominal = $mou->invoices->flatMap->costListInvoices->sum('amount');
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $mouNo++ }}</td>
                                <td class="px-6 py-4 text-sm text-slate-900 dark:text-white whitespace-nowrap font-semibold">{{ $mou->mou_number ?: '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate" title="{{ $mou->description }}">{{ $mou->description ?: '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ $mou->categoryMou->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-right text-slate-900 dark:text-white whitespace-nowrap font-semibold">
                                    Rp {{ number_format($totalCostList, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-emerald-600 dark:text-emerald-400 whitespace-nowrap font-semibold">
                                    {{ $totalInvoiceNominal > 0 ? 'Rp ' . number_format($totalInvoiceNominal, 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-amber-600 dark:text-amber-400 whitespace-nowrap font-semibold">
                                    {{ $mou->discount_amount > 0 ? 'Rp ' . number_format($mou->discount_amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-rose-600 dark:text-rose-400 whitespace-nowrap font-semibold">
                                    {{ $mou->cancel_mou_amount > 0 ? 'Rp ' . number_format($mou->cancel_mou_amount, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-sm text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <i class="fa-regular fa-folder-open text-3xl text-slate-300 dark:text-slate-700"></i>
                                        <span>Tidak ada data MoU untuk client ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800 print-card">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-blue-600"></i> Mutasi Transaksi Kronologis
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 print-table">
                    <thead class="bg-slate-50/75 dark:bg-slate-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-16">No</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipe</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Referensi</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Deskripsi</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Debit (+)</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kredit (-)</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Saldo Piutang</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-900 dark:divide-slate-800">
                        @php $no = 1; @endphp
                        @forelse($transactions as $tx)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $no++ }}</td>
                                <td class="px-6 py-4 text-sm text-slate-900 dark:text-white whitespace-nowrap font-medium">
                                    {{ $tx['date'] ? \Carbon\Carbon::parse($tx['date'])->translatedFormat('d-M-Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap">
                                     @if($tx['type'] === 'Saldo Awal')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/30">
                                            {{ $tx['type'] }}
                                        </span>
                                    @elseif($tx['type'] === 'Sales Invoice')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/30">
                                            Invoice
                                        </span>
                                    @elseif($tx['type'] === 'Sales Receipt')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/30">
                                            Pembayaran
                                        </span>
                                    @elseif($tx['type'] === 'Discount MoU')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-purple-50 text-purple-700 border border-purple-100 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-900/30">
                                            Diskon MoU
                                        </span>
                                    @elseif($tx['type'] === 'Cancel MoU')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-900/30">
                                            Cancel MoU
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-slate-50 text-slate-700 border border-slate-100 dark:bg-slate-900/20 dark:text-slate-400 dark:border-slate-800">
                                            {{ $tx['type'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-900 dark:text-white whitespace-nowrap font-semibold">{{ $tx['ref'] }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-sm truncate" title="{{ $tx['description'] }}">{{ $tx['description'] }}</td>
                                <td class="px-6 py-4 text-sm text-right text-slate-900 dark:text-white whitespace-nowrap font-semibold">
                                    {{ $tx['debit'] > 0 ? 'Rp ' . number_format($tx['debit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-slate-900 dark:text-white whitespace-nowrap font-semibold">
                                    {{ $tx['kredit'] > 0 ? 'Rp ' . number_format($tx['kredit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-slate-950 dark:text-white whitespace-nowrap font-bold">
                                    Rp {{ number_format($tx['running_balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-sm text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <i class="fa-regular fa-folder-open text-3xl text-slate-300 dark:text-slate-700"></i>
                                        <span>Tidak ada data transaksi ditemukan.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Saldo Awal Piutang -->
    <div id="saldoAwalModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4 no-print">
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-md p-6 overflow-hidden transform transition-all">
            <div class="flex justify-between items-center pb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ $saldoAwalRecord ? 'Edit Saldo Awal Piutang' : 'Tambah Saldo Awal Piutang' }}
                </h3>
                <button onclick="closeSaldoAwalModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form action="{{ route('piutang-per-client.saldo-awal.update', $client->id) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="year" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Periode / Tahun</label>
                    <select name="year" id="year" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm">
                        <option value="2024">Sebelum 2025 (<= 2024)</option>
                        <option value="2025">Tahun 2025</option>
                        <option value="2026">Tahun 2026</option>
                        <option value="2027">Tahun 2027</option>
                    </select>
                </div>
                <div>
                    <label for="amount" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Jumlah Saldo Awal (Rp)</label>
                    <input type="number" step="any" name="amount" id="amount" value="{{ old('amount', $saldoAwalRecord->amount ?? 0) }}" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm">
                </div>
                <div>
                    <label for="notes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Keterangan (Opsional)</label>
                    <input type="text" name="notes" id="notes" value="{{ old('notes', $saldoAwalRecord->notes ?? '') }}" placeholder="Contoh: Saldo cut-off sebelum 2025" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeSaldoAwalModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 text-sm font-semibold transition-colors">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle Theme Function
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('theme-icon');
            
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                localStorage.setItem('cashReferenceTheme', 'light');
                icon.className = 'fa-solid fa-moon text-sm';
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                localStorage.setItem('cashReferenceTheme', 'dark');
                icon.className = 'fa-solid fa-sun text-sm';
            }
        }

        function openSaldoAwalModal() {
            document.getElementById('saldoAwalModal').classList.remove('hidden');
        }

        function closeSaldoAwalModal() {
            document.getElementById('saldoAwalModal').classList.add('hidden');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('saldoAwalModal');
            if (event.target === modal) {
                closeSaldoAwalModal();
            }
        }

        // Set initial icon on load
        document.addEventListener('DOMContentLoaded', () => {
            const icon = document.getElementById('theme-icon');
            if (document.documentElement.classList.contains('dark')) {
                icon.className = 'fa-solid fa-sun text-sm';
            } else {
                icon.className = 'fa-solid fa-moon text-sm';
            }
        });
    </script>
</body>

</html>
