@php
    $months = $data['selectedSemester'] == 1 ? ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'] : ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
@endphp

<x-filament-panels::page>
    <div class="mb-4">
        {{ $this->form }}
    </div>

    @foreach ($staffReports as $reportType => $reportData)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6">
            <div class="fi-section-header flex flex-col gap-y-1 px-6 py-4">
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $reportData['label'] }} - Tahun {{ $data['selectedYear'] }} - Semester {{ $data['selectedSemester'] }}
                </h3>
            </div>
            <div class="fi-section-content px-6 py-4 sm:px-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-950 dark:text-white">Nama Klien</th>
                                @foreach ($months as $month)
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-950 dark:text-white">{{ $month }}</th>
                                @endforeach
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-950 dark:text-white">Skor Klien</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-950 dark:text-white">Sasaran</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-950 dark:text-white">Hasil</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-950 dark:text-white">Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($reportData['clients'] as $clientData)
                                {{-- Baris untuk Tanggal Laporan --}}
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">{{ $clientData['name'] }}</td>
                                    @foreach ($months as $month)
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $clientData['months'][$month]['report_date'] }}
                                        </td>
                                    @endforeach

                                    @php
                                        $scores = collect($clientData['months'])
                                            ->pluck('score')
                                            ->filter(function ($value) {
                                                return !is_null($value) && $value !== '';
                                            });
                                        $average = $scores->isNotEmpty() ? number_format($scores->avg(), 2) : '-';
                                    @endphp
                                    <td rowspan="2" class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $average }}
                                    </td>

                                    @if ($loop->first)
                                        @php
                                            $totalRows = count($reportData['clients']) * 2;
                                            $targetScore = $reportType === 'pph25' ? 1.0 : 2.0; // Default target score

                                            if ($scores->isNotEmpty()) {
                                                $result = ($scores->avg() / $targetScore) * 100;
                                                $resultFormatted = number_format($result, 2) . '%';
                                                $nilai = number_format($result * 0.05, 2);
                                            } else {
                                                $resultFormatted = '-';
                                                $nilai = '-';
                                            }
                                        @endphp
                                        <td rowspan="{{ $totalRows }}" class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                            {{ $targetScore }}
                                        </td>
                                        <td rowspan="{{ $totalRows }}" class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                            {{ $resultFormatted }}
                                        </td>
                                        <td rowspan="{{ $totalRows }}" class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                            {{ $nilai }}
                                        </td>
                                    @endif
                                </tr>
                                {{-- Baris untuk Skor --}}
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">Skor</td>
                                    @foreach ($months as $month)
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $clientData['months'][$month]['score_display'] }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($months) + 5 }}" class="px-3 py-4 text-sm text-center text-gray-500 dark:text-gray-400">
                                        Tidak ada data laporan untuk periode ini
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</x-filament-panels::page>
