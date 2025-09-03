<x-filament-panels::page>
    <x-filament::card>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium">
                Periode: {{ \Carbon\Carbon::create($this->tahun, $this->bulan)->translatedFormat('F Y') }}
            </h2>
        </div>
    </x-filament::card>

    <x-filament::card>
        <div class="overflow-x-auto">
            <table class="w-full text-xs leading-tight">
                <thead>
                    <tr class="sticky top-0 z-20 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/75">
                        <th
                            class="sticky left-0 z-30 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/75 px-3 py-2 text-left font-semibold text-gray-700 border-b">
                            Tanggal
                        </th>
                        @php
                            $staff = \App\Models\Staff::where('is_active', true)->orderBy('name')->get();
                        @endphp
                        @foreach ($staff as $staffMember)
                            <th class="px-3 py-2 text-center font-semibold text-gray-700 whitespace-nowrap border-b">
                                {{ $staffMember->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="[&_tr:nth-child(even)]:bg-gray-50">
                    @php
                        $startDate = \Carbon\Carbon::create($this->tahun, $this->bulan, 1);
                        $endDate = $startDate->copy()->endOfMonth();
                        $dates = [];

                        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                            $dates[] = $date->format('Y-m-d');
                        }

                        $attendanceData = \App\Models\StaffAttendance::whereMonth('tanggal', $this->bulan)
                            ->whereYear('tanggal', $this->tahun)
                            ->get()
                            ->groupBy(['tanggal', 'staff_id']);
                    @endphp

                    @foreach ($dates as $date)
                        @php
                            $dateObj = \Carbon\Carbon::parse($date);
                            $dayName = $dateObj->locale('id')->translatedFormat('D');
                            $dayNumber = $dateObj->format('d');
                            $isWeekend = $dateObj->isWeekend();
                        @endphp
                        <tr class="hover:bg-gray-100">
                            <td
                                class="sticky left-0 z-10 px-3 py-2 border-y font-medium text-gray-900 {{ $isWeekend ? 'bg-amber-50' : 'bg-white' }}">
                                <div class="text-gray-500">{{ $dayName }}</div>
                                <div class="text-sm font-bold">{{ $dayNumber }}</div>
                            </td>
                            @foreach ($staff as $staffMember)
                                @php
                                    $attendance = $attendanceData->get($date)?->get($staffMember->id);
                                @endphp
                                <td
                                    class="px-2 py-2 text-center align-top border-y {{ $isWeekend ? 'bg-amber-50/40' : '' }}">
                                    @if ($attendance)
                                        @php
                                            $attendance = $attendance->first();
                                            $jamMasuk = $attendance->jam_masuk
                                                ? \Carbon\Carbon::parse($attendance->jam_masuk)->format('H:i')
                                                : '-';
                                            $jamPulang = $attendance->jam_pulang
                                                ? \Carbon\Carbon::parse($attendance->jam_pulang)->format('H:i')
                                                : '-';

                                            [$badgeBg, $badgeText, $badgeRing] = match ($attendance->status) {
                                                'masuk' => ['bg-emerald-50', 'text-emerald-700', 'ring-emerald-200'],
                                                'sakit' => ['bg-blue-50', 'text-blue-700', 'ring-blue-200'],
                                                'izin' => ['bg-amber-50', 'text-amber-700', 'ring-amber-200'],
                                                'cuti' => ['bg-cyan-50', 'text-cyan-700', 'ring-cyan-200'],
                                                'alfa' => ['bg-rose-50', 'text-rose-700', 'ring-rose-200'],
                                                default => ['bg-gray-50', 'text-gray-700', 'ring-gray-200'],
                                            };
                                        @endphp
                                        <div
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $badgeBg }} {{ $badgeText }} {{ $badgeRing }} ring-inset uppercase">
                                            {{ $attendance->status }}
                                        </div>
                                        <div class="mt-1 text-[10px] text-gray-600">
                                            Masuk: <span class="font-medium">{{ $jamMasuk }}</span>
                                        </div>
                                        <div class="text-[10px] text-gray-600">
                                            Pulang: <span class="font-medium">{{ $jamPulang }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::card>

</x-filament-panels::page>
