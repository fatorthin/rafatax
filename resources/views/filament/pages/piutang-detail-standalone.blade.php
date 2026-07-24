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
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Header / Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 no-print">
            <div class="flex items-center gap-4">
                <a href="/app/piutang-per-client" class="inline-flex items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <nav class="flex text-xs text-slate-500 mb-1 gap-1.5 items-center dark:text-slate-400">
                        <span>Keuangan</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <a href="/app/piutang-per-client" class="hover:underline">Piutang per Client</a>
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

        <!-- Summary Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Saldo Awal -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800 print-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Saldo Awal Piutang</span>
                    <span class="p-2 rounded-xl bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <i class="fa-solid fa-wallet"></i>
                    </span>
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
