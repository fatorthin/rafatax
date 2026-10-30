<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi Bank: {{ $coa ? $coa->code . ' - ' . $coa->name : 'Semua Akun' }} ({{ $monthName }} {{ $year }})</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Outfit', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        };
    </script>

    <script>
        // Init theme dark/light
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

        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            localStorage.setItem('cashReferenceTheme', isDark ? 'dark' : 'light');
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .font-heading {
            font-family: 'Outfit', sans-serif;
        }

        .dark body {
            background-color: #0b0f19;
            color: #f3f4f6;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        .dark ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">
    @php
        $isAdminRoute = request()->is('admin/*') || $from === 'admin';
        $entityParam = (!empty($entity) && $entity !== 'all') ? '&entity=' . $entity : '';
        $backUrl = url('/admin/cash-reports/neraca-lajur-piutang?month=' . $month . '&year=' . $year . $entityParam);
        if (!$isAdminRoute && $from === 'app') {
            $backUrl = url('/app/neraca-lajur-piutang?month=' . $month . '&year=' . $year . $entityParam);
        }

        // Filter data agar cash_reference dengan id 5 (MANDIRI) tidak ditampilkan
        $transactions = collect($transactions)->reject(function ($trx) {
            $refId = is_array($trx) ? ($trx['cash_reference_id'] ?? null) : ($trx->cash_reference_id ?? null);
            return (int) $refId === 5;
        })->values();

        $summaryByBank = collect($summaryByBank)->reject(function ($bank, $key) {
            $refId = is_array($bank) ? ($bank['id'] ?? $key) : ($bank->id ?? $key);
            return (int) $refId === 5;
        })->all();

        $totalAmount = $transactions->sum('amount');
        $totalCount = $transactions->count();
    @endphp

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Left: Back Button & Title -->
                <div class="flex items-center gap-3">
                    <a href="{{ $backUrl }}" 
                       class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-colors shadow-sm"
                       title="Kembali ke Neraca Lajur">
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $type === 'kredit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' }}">
                                Kolom Bank {{ strtoupper($type) }}
                            </span>
                            @if(!empty($entity) && $entity !== 'all')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $entity === 'pt' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' }}">
                                    Entitas: {{ strtoupper($entity) }}
                                </span>
                            @endif
                            <span class="text-xs text-slate-400 dark:text-slate-500">•</span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ $monthName }} {{ $year }}
                            </span>
                        </div>
                        <h1 class="font-heading text-lg font-bold text-slate-900 dark:text-white leading-tight">
                            Detail Transaksi Kas Bank
                        </h1>
                    </div>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex items-center gap-2">
                    <!-- Export Excel -->
                    <a href="{{ route('neraca-lajur-piutang.detail-bank.export', request()->all()) }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm shadow-emerald-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                        <i class="fa-solid fa-file-excel text-sm"></i>
                        <span class="hidden sm:inline">Export Excel</span>
                    </a>

                    <!-- Kembali button -->
                    <a href="{{ $backUrl }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 text-xs font-medium transition-colors shadow-sm">
                        <i class="fa-solid fa-table-cells text-xs text-slate-400"></i>
                        <span class="hidden sm:inline">Neraca Lajur</span>
                    </a>

                    <!-- Theme Toggle -->
                    <button onclick="toggleTheme()" 
                            type="button"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors shadow-sm"
                            title="Toggle Dark / Light Mode">
                        <i class="fa-solid fa-moon hidden dark:inline text-sm text-yellow-400"></i>
                        <i class="fa-solid fa-sun dark:hidden text-sm text-amber-500"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        
        <!-- Account Info Banner -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 p-6 text-white shadow-xl">
            <!-- Decorative Background glow -->
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute right-40 -top-10 w-48 h-48 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 border border-blue-400/30 text-blue-200">
                            <i class="fa-solid fa-hashtag text-[10px]"></i>
                            Kode Akun: {{ $coa ? $coa->code : 'Semua CoA' }}
                        </span>
                        @if($coa && $coa->groupCoa)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/10 text-slate-300">
                                {{ $coa->groupCoa->name }}
                            </span>
                        @endif
                    </div>
                    <h2 class="font-heading text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                        {{ $coa ? $coa->name : 'Rekapitulasi Seluruh Akun Kas Bank' }}
                    </h2>
                    <p class="text-sm text-blue-200/80 mt-1 flex items-center gap-2">
                        <span><i class="fa-regular fa-calendar-days mr-1"></i> Periode {{ $monthName }} {{ $year }}</span>
                        <span>•</span>
                        <span><i class="fa-solid fa-building-columns mr-1"></i> Berasal dari Kas Bank (BCA PT, BCA 425, BCA 140, BCA 436)</span>
                    </p>
                </div>

                <!-- Total Amount Pill -->
                <div class="flex flex-col sm:items-end justify-center bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/15 shadow-inner">
                    <span class="text-xs uppercase tracking-wider font-semibold text-blue-200">
                        Total Nilai di Kolom Bank ({{ strtoupper($type) }})
                    </span>
                    <span class="font-heading text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-0.5">
                        Rp {{ number_format($totalAmount, 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-blue-200/80 mt-1">
                        dari total <strong class="text-white">{{ $totalCount }}</strong> transaksi
                    </span>
                </div>
            </div>
        </div>

        <!-- Section: Breakdown per Kas Bank -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-blue-600 dark:text-blue-400"></i>
                    <h3 class="font-heading text-base font-bold text-slate-900 dark:text-white">
                        Rincian Asal Kas Bank
                    </h3>
                    <span class="text-xs text-slate-400">({{ count($summaryByBank) }} Rekening Terlibat)</span>
                </div>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Klik kartu untuk memfilter tabel
                </span>
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3.5">
                <!-- Card: Semua Bank -->
                <div onclick="filterByBank(null)" 
                     id="card-bank-all"
                     class="bank-card active group cursor-pointer relative overflow-hidden rounded-xl border border-blue-500 bg-blue-50/70 dark:bg-blue-950/40 p-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Semua Bank</span>
                            <div class="font-heading text-lg font-bold text-slate-900 dark:text-white mt-1">
                                Rp {{ number_format($totalAmount, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-blue-500 text-white flex items-center justify-center shadow-sm">
                            <i class="fa-solid fa-layer-group text-xs"></i>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-blue-200/60 dark:border-blue-900/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>{{ $totalCount }} transaksi</span>
                        <span class="font-semibold text-blue-600 dark:text-blue-400">100%</span>
                    </div>
                </div>

                <!-- Individual Bank Cards -->
                @foreach($summaryByBank as $bankId => $bankData)
                    @if($bankId == 5 || (isset($bankData['id']) && $bankData['id'] == 5))
                        @continue
                    @endif
                    @php
                        $percent = $totalAmount > 0 ? round(($bankData['total_amount'] / $totalAmount) * 100, 1) : 0;
                        $isPt = strtoupper($bankData['status_kepemilikan'] ?? '') === 'PT';
                        $bankName = $bankData['name'];
                        
                        // Icon & color theme based on bank
                        $iconClass = 'fa-building-columns';
                        $badgeBg = 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
                        if (str_contains(strtoupper($bankName), 'MANDIRI')) {
                            $badgeBg = 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
                        } elseif (str_contains(strtoupper($bankName), 'BCA')) {
                            $badgeBg = 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300';
                        }
                    @endphp
                    <div onclick="filterByBank({{ $bankId }})" 
                         id="card-bank-{{ $bankId }}"
                         class="bank-card cursor-pointer group relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 transition-all duration-200 hover:border-blue-400 hover:shadow-md hover:-translate-y-0.5">
                        <div class="flex items-start justify-between">
                            <div class="truncate pr-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">
                                        {{ $bankName }}
                                    </span>
                                    @if($bankData['status_kepemilikan'])
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold {{ $isPt ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                            {{ $bankData['status_kepemilikan'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="font-heading text-lg font-bold text-slate-900 dark:text-white mt-1">
                                    Rp {{ number_format($bankData['total_amount'], 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="w-8 h-8 rounded-lg {{ $badgeBg }} flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i class="fa-solid {{ $iconClass }} text-xs"></i>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>{{ $bankData['count'] }} transaksi</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $percent }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Section: Detail Transactions Table -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden transition-colors">
            
            <!-- Table Header Controls -->
            <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    
                    <!-- Search input -->
                    <div class="relative flex-1 max-w-lg">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </div>
                        <input type="text" 
                               id="tableSearch" 
                               onkeyup="handleSearch()"
                               placeholder="Cari transaksi (keterangan, invoice, klien, nominal)..." 
                               class="w-full pl-9 pr-8 py-2 rounded-xl text-xs border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all shadow-sm">
                        <button id="clearSearchBtn" onclick="clearSearch()" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-circle-xmark text-xs"></i>
                        </button>
                    </div>

                    <!-- Right Info / Counters -->
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span id="filteredCounter" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-medium">
                            <i class="fa-solid fa-list-check text-blue-500"></i>
                            <span>Menampilkan <strong id="visibleCount" class="text-slate-800 dark:text-white">{{ $totalCount }}</strong> dari {{ $totalCount }} data</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 font-semibold">
                            Total: <span id="visibleTotal">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="overflow-x-auto max-h-[650px] relative">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="sticky top-0 z-20 bg-slate-100/95 dark:bg-slate-800/95 backdrop-blur text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-700 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-3 text-center w-12">No</th>
                            <th class="py-3 px-3 text-center w-28">Tanggal</th>
                            <th class="py-3 px-3 text-center w-36">Kas / Bank</th>
                            <th class="py-3 px-3 w-40">No. Invoice</th>
                            <th class="py-3 px-3 w-56">Klien / Perusahaan</th>
                            <th class="py-3 px-3">Keterangan / Deskripsi</th>
                            <th class="py-3 px-4 text-right w-40">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody id="transactionTableBody" class="divide-y divide-slate-100 dark:divide-slate-800/60 font-normal">
                        @forelse($transactions as $index => $trx)
                            @if(($trx['cash_reference_id'] ?? null) == 5)
                                @continue
                            @endif
                            @php
                                $bankId = $trx['cash_reference_id'];
                                $searchData = strtolower(implode(' ', [
                                    $trx['bank_name'],
                                    $trx['invoice_number'] ?? '',
                                    $trx['client_name'] ?? '',
                                    $trx['description'] ?? '',
                                    \Carbon\Carbon::parse($trx['transaction_date'])->format('d/m/Y'),
                                    number_format($trx['amount'], 0, '', ''),
                                    number_format($trx['amount'], 0, ',', '.')
                                ]));
                                
                                $isPt = strtoupper($trx['status_kepemilikan'] ?? '') === 'PT';
                            @endphp
                            <tr class="trx-row hover:bg-blue-50/50 dark:hover:bg-slate-800/60 transition-colors"
                                data-bank-id="{{ $bankId }}"
                                data-amount="{{ $trx['amount'] }}"
                                data-search="{{ $searchData }}">
                                <td class="py-2.5 px-3 text-center text-slate-400 dark:text-slate-500 font-mono text-[11px] row-num">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap font-medium text-slate-600 dark:text-slate-300">
                                    {{ \Carbon\Carbon::parse($trx['transaction_date'])->format('d M Y') }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                        <i class="fa-solid fa-building-columns text-[10px] text-blue-500"></i>
                                        {{ $trx['bank_name'] }}
                                        @if($trx['status_kepemilikan'] && $trx['status_kepemilikan'] !== '-')
                                            <span class="text-[9px] font-bold px-1 rounded {{ $isPt ? 'bg-purple-200/80 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : 'bg-emerald-200/80 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300' }}">
                                                {{ $trx['status_kepemilikan'] }}
                                            </span>
                                        @endif
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    @if($trx['invoice_number'])
                                        <span class="inline-flex items-center gap-1 font-mono font-medium text-blue-600 dark:text-blue-400">
                                            <i class="fa-regular fa-file-lines text-[10px]"></i>
                                            {{ $trx['invoice_number'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3">
                                    @if($trx['client_name'])
                                        <span class="font-medium text-slate-800 dark:text-slate-200 line-clamp-1" title="{{ $trx['client_name'] }}">
                                            {{ $trx['client_name'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">
                                    <div class="line-clamp-2" title="{{ $trx['description'] }}">
                                        {{ $trx['description'] }}
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 text-right whitespace-nowrap font-mono font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format($trx['amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyInitialRow">
                                <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                                    Tidak ada transaksi bank yang tercatat untuk akun ini pada periode {{ $monthName }} {{ $year }}.
                                </td>
                            </tr>
                        @endforelse

                        <!-- Empty state when filtered out -->
                        <tr id="noResultsRow" class="hidden">
                            <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-2 text-slate-300 dark:text-slate-600 block"></i>
                                Tidak ditemukan transaksi yang cocok dengan kata kunci atau filter yang dipilih.
                                <div class="mt-2">
                                    <button onclick="resetFilters()" class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-semibold">
                                        Reset Filter & Pencarian
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    <!-- Table Footer Total -->
                    <tfoot class="sticky bottom-0 z-10 bg-slate-100/95 dark:bg-slate-800/95 backdrop-blur font-bold border-t-2 border-slate-300 dark:border-slate-700">
                        <tr>
                            <td colspan="6" class="py-3 px-4 text-right uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Total Transaksi Terfilter:
                            </td>
                            <td id="footerTotal" class="py-3 px-4 text-right font-mono text-sm text-slate-900 dark:text-white font-extrabold whitespace-nowrap">
                                Rp {{ number_format($totalAmount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Back to Neraca Lajur Footer link -->
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2">
            <div>
                Data diperbarui secara real-time dari buku kas bank (ID 1 s/d 4) pada modul Keuangan Rafatax.
            </div>
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 hover:underline font-medium">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                Kembali ke Neraca Lajur (Konsep Piutang)
            </a>
        </div>
    </main>

    <!-- Client-side Interactive Filter & Search Script -->
    <script>
        let selectedBankId = null;

        function filterByBank(bankId) {
            selectedBankId = bankId;

            // Update card active classes
            document.querySelectorAll('.bank-card').forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50/70', 'dark:bg-blue-950/40', 'ring-2', 'ring-blue-400');
                card.classList.add('border-slate-200', 'dark:border-slate-800');
            });

            const activeCardId = bankId === null ? 'card-bank-all' : 'card-bank-' + bankId;
            const activeCard = document.getElementById(activeCardId);
            if (activeCard) {
                activeCard.classList.remove('border-slate-200', 'dark:border-slate-800');
                activeCard.classList.add('border-blue-500', 'bg-blue-50/70', 'dark:bg-blue-950/40', 'ring-2', 'ring-blue-400');
            }

            applyFilters();
        }

        function handleSearch() {
            const searchInput = document.getElementById('tableSearch');
            const clearBtn = document.getElementById('clearSearchBtn');
            if (searchInput.value.trim() !== '') {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
            applyFilters();
        }

        function clearSearch() {
            const searchInput = document.getElementById('tableSearch');
            searchInput.value = '';
            document.getElementById('clearSearchBtn').classList.add('hidden');
            applyFilters();
            searchInput.focus();
        }

        function resetFilters() {
            document.getElementById('tableSearch').value = '';
            document.getElementById('clearSearchBtn').classList.add('hidden');
            filterByBank(null);
        }

        function applyFilters() {
            const searchTerm = (document.getElementById('tableSearch').value || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.trx-row');
            let visibleCount = 0;
            let visibleSum = 0;

            rows.forEach(row => {
                const rowBankId = row.getAttribute('data-bank-id');
                const rowAmount = parseFloat(row.getAttribute('data-amount') || 0);
                const rowSearchData = row.getAttribute('data-search') || '';

                const matchesBank = (selectedBankId === null) || (String(rowBankId) === String(selectedBankId));
                const matchesSearch = !searchTerm || rowSearchData.includes(searchTerm);

                if (matchesBank && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                    visibleSum += rowAmount;
                    
                    // Renumber visible row
                    const numCell = row.querySelector('.row-num');
                    if (numCell) {
                        numCell.innerText = visibleCount;
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            // Update counter and totals
            document.getElementById('visibleCount').innerText = visibleCount;
            const formattedTotal = 'Rp ' + new Intl.NumberFormat('id-ID').format(visibleSum);
            document.getElementById('visibleTotal').innerText = formattedTotal;
            document.getElementById('footerTotal').innerText = formattedTotal;

            // Empty state row
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                if (visibleCount === 0 && rows.length > 0) {
                    noResultsRow.classList.remove('hidden');
                } else {
                    noResultsRow.classList.add('hidden');
                }
            }
        }
    </script>
</body>

</html>
