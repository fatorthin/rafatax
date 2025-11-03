<x-filament-panels::page>
    <div class="space-y-6" x-data="payrollDetailComponent()">
        <!-- Payroll Info Card -->
        <x-filament::card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Payroll Name</h3>
                    <p class="mt-1 text-lg font-semibold">{{ $record->name }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Period</h3>
                    <p class="mt-1 text-lg font-semibold">
                        {{ \Carbon\Carbon::parse($record->payroll_date)->format('F Y') }}</p>
                </div>
            </div>
        </x-filament::card>

        <!-- Payroll Details Table -->
        <x-filament::card>
            <div class="space-y-4">
                <h2 class="text-xl font-bold">Payroll Details</h2>

                @if ($record->details->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Staff Name</th>
                                    <th class="px-4 py-3 text-right font-medium">Salary</th>
                                    <th class="px-4 py-3 text-right font-medium">Bonus Position</th>
                                    <th class="px-4 py-3 text-right font-medium">Bonus Competency</th>
                                    <th class="px-4 py-3 text-right font-medium">Bonus Lain</th>
                                    <th class="px-4 py-3 text-right font-medium">Total Bonus</th>
                                    <th class="px-4 py-3 text-right font-medium">BPJS Kesehatan</th>
                                    <th class="px-4 py-3 text-right font-medium">BPJS Ketenagakerjaan</th>
                                    <th class="px-4 py-3 text-right font-medium">Cut Lain</th>
                                    <th class="px-4 py-3 text-right font-medium">Cut Hutang</th>
                                    <th class="px-4 py-3 text-right font-medium">Total Cut</th>
                                    <th class="px-4 py-3 text-right font-medium">Net Salary</th>
                                    <th class="px-4 py-3 text-center font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @php
                                    $totalSalary = 0;
                                    $totalBonusPosition = 0;
                                    $totalBonusCompetency = 0;
                                    $totalBonusLain = 0;
                                    $totalCutBPJSKesehatan = 0;
                                    $totalCutBPJSKetenagakerjaan = 0;
                                    $totalCutLain = 0;
                                    $totalCutHutang = 0;
                                    $totalNet = 0;
                                @endphp

                                @foreach ($record->details as $detail)
                                    @php
                                        $totalBonus =
                                            $detail->bonus_position + $detail->bonus_competency + $detail->bonus_lain;
                                        $totalCut =
                                            $detail->cut_bpjs_kesehatan +
                                            $detail->cut_bpjs_ketenagakerjaan +
                                            $detail->cut_lain +
                                            $detail->cut_hutang;
                                        $netSalary = $detail->salary + $totalBonus - $totalCut;

                                        $totalSalary += $detail->salary;
                                        $totalBonusPosition += $detail->bonus_position;
                                        $totalBonusCompetency += $detail->bonus_competency;
                                        $totalBonusLain += $detail->bonus_lain;
                                        $totalCutBPJSKesehatan += $detail->cut_bpjs_kesehatan;
                                        $totalCutBPJSKetenagakerjaan += $detail->cut_bpjs_ketenagakerjaan;
                                        $totalCutLain += $detail->cut_lain;
                                        $totalCutHutang += $detail->cut_hutang;
                                        $totalNet += $netSalary;
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 font-medium">{{ $detail->staff->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            {{ number_format($detail->salary, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            {{ number_format($detail->bonus_position, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            {{ number_format($detail->bonus_competency, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            {{ number_format($detail->bonus_lain, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">
                                            {{ number_format($totalBonus, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-red-600">
                                            {{ number_format($detail->cut_bpjs_kesehatan, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-red-600">
                                            {{ number_format($detail->cut_bpjs_ketenagakerjaan, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-red-600">
                                            {{ number_format($detail->cut_lain, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-red-600">
                                            {{ number_format($detail->cut_hutang, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-red-600">
                                            {{ number_format($totalCut, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-green-600">
                                            {{ number_format($netSalary, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <!-- Download Button -->
                                                <a href="{{ route('payroll.download-slip', ['detail' => $detail->id]) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md border transition-colors duration-150
                                                           bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600
                                                           border-gray-300 dark:border-gray-600
                                                           text-gray-700 dark:text-gray-200
                                                           focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800"
                                                    title="Download Slip Gaji PDF">
                                                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                        </path>
                                                    </svg>
                                                    <span>Download</span>
                                                </a>

                                                <!-- WhatsApp Send Button -->
                                                <button type="button"
                                                    @click="openWablasModal({{ $detail->id }}, '{{ addslashes($detail->staff->name ?? '') }}', '{{ $detail->staff->phone ?? '' }}')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md border transition-colors duration-150
                                                           bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600
                                                           border-gray-300 dark:border-gray-600
                                                           text-gray-700 dark:text-gray-200
                                                           focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-gray-800"
                                                    title="Kirim Slip Gaji via WhatsApp">
                                                    <svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400"
                                                        fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                                    </svg>
                                                    <span>Kirim</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Total Row -->
                                <tr class="bg-gray-100 dark:bg-gray-800 font-bold">
                                    <td class="px-4 py-3">TOTAL</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($totalSalary, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        {{ number_format($totalBonusPosition, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        {{ number_format($totalBonusCompetency, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($totalBonusLain, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        {{ number_format($totalBonusPosition + $totalBonusCompetency + $totalBonusLain, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-red-600">
                                        {{ number_format($totalCutBPJSKesehatan, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-red-600">
                                        {{ number_format($totalCutBPJSKetenagakerjaan, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-red-600">
                                        {{ number_format($totalCutLain, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-red-600">
                                        {{ number_format($totalCutHutang, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-red-600">
                                        {{ number_format($totalCutBPJSKesehatan + $totalCutBPJSKetenagakerjaan + $totalCutLain + $totalCutHutang, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-600 text-lg">
                                        {{ number_format($totalNet, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        No payroll details found.
                    </div>
                @endif
            </div>
        </x-filament::card>

        <!-- Wablas Send Confirmation Modal -->
        <div x-show="showModal" x-cloak @click.self="showModal = false" class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop with better dark mode support -->
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" aria-hidden="true">
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 border border-gray-200 dark:border-gray-700">
                    <div class="sm:flex sm:items-start">
                        <!-- Icon -->
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-green-100 dark:bg-green-900/30 sm:mx-0">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100"
                                id="modal-title">
                                Kirim Slip Gaji via WhatsApp
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Kirim slip gaji untuk <strong class="font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="selectedStaffName"></strong> ke nomor <strong
                                        class="font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="selectedStaffPhone"></strong>?
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <!-- Primary Button (Kirim) - Fixed visibility for light theme -->
                        <button type="button" @click="confirmSendWablas()" :disabled="sending"
                            class="mt-3 w-full inline-flex justify-center rounded-md px-4 py-2 text-sm font-medium transition-all sm:mt-0 sm:w-auto
                                   border
                                   bg-primary-600  active:bg-gray-100
                                   dark:bg-gray-700  dark:active:bg-gray-500
                                   border-gray-300 dark:border-gray-600
                                   text-white dark:text-gray-200
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400
                                   dark:focus:ring-gray-500 dark:focus:ring-offset-gray-800
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   shadow-sm hover:shadow">
                            <svg x-show="sending" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="sending ? 'Mengirim...' : 'Kirim'"></span>
                        </button>

                        <!-- Secondary Button (Batal) -->
                        <button type="button" @click="showModal = false" :disabled="sending"
                            class="mt-3 w-full inline-flex justify-center rounded-md px-4 py-2 text-sm font-medium transition-all sm:mt-0 sm:w-auto
                                   border
                                   bg-white hover:bg-gray-50 active:bg-gray-100
                                   dark:bg-gray-700 dark:hover:bg-gray-600 dark:active:bg-gray-500
                                   border-gray-300 dark:border-gray-600
                                   text-gray-700 dark:text-gray-200
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400
                                   dark:focus:ring-gray-500 dark:focus:ring-offset-gray-800
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   shadow-sm hover:shadow">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function payrollDetailComponent() {
            return {
                showModal: false,
                sending: false,
                selectedDetailId: null,
                selectedStaffName: '',
                selectedStaffPhone: '',

                openWablasModal(detailId, staffName, staffPhone) {
                    if (!staffPhone || staffPhone === '') {
                        alert('Nomor WhatsApp staff tidak tersedia!');
                        return;
                    }

                    this.selectedDetailId = detailId;
                    this.selectedStaffName = staffName;
                    this.selectedStaffPhone = staffPhone;
                    this.showModal = true;
                },

                async confirmSendWablas() {
                    if (!this.selectedDetailId || this.sending) return;

                    this.sending = true;

                    try {
                        const response = await fetch(`/app/payroll-detail/${this.selectedDetailId}/send-wablas`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                                    '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showModal = false;

                            // Show success notification using Filament
                            new FilamentNotification()
                                .title('Berhasil!')
                                .success()
                                .body(data.message || 'Slip gaji berhasil dikirim via WhatsApp!')
                                .send();
                        } else {
                            alert(data.message || 'Gagal mengirim slip gaji!');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengirim slip gaji!');
                    } finally {
                        this.sending = false;
                    }
                }
            }
        }
    </script>
</x-filament-panels::page>
