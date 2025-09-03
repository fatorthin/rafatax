<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Transaksi Kas: {{ $this->record->name }}
                    </h2>
                    <p class="text-gray-600 mt-1">
                        {{ $this->record->description ?: 'Tidak ada deskripsi' }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Total Saldo</div>
                    <div class="text-2xl font-bold text-green-600">
                        @php
                            $debitTotal = $this->record->cashReports()->sum('debit_amount');
                            $creditTotal = $this->record->cashReports()->sum('credit_amount');
                            $total = $debitTotal - $creditTotal;
                        @endphp
                        Rp {{ number_format($total, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Total Transaksi</div>
                <div class="text-2xl font-bold text-blue-600">
                    {{ $this->record->cashReports()->count() }}
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Total Debit</div>
                <div class="text-2xl font-bold text-green-600">
                    Rp
                    {{ number_format($this->record->cashReports()->where('type', 'debit')->sum('debit_amount'), 0, ',', '.') }}
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Total Credit</div>
                <div class="text-2xl font-bold text-red-600">
                    Rp
                    {{ number_format($this->record->cashReports()->where('type', 'credit')->sum('credit_amount'), 0, ',', '.') }}
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Transaksi Manual</div>
                <div class="text-2xl font-bold text-gray-600">
                    {{ $this->record->cashReports()->where('type', 'manual')->count() }}
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Daftar Transaksi</h3>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
