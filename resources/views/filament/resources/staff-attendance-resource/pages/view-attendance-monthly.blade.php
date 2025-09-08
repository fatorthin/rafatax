<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::card>
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-lg font-semibold text-gray-900">
                    Periode: {{ \Carbon\Carbon::create($this->tahun, $this->bulan)->translatedFormat('F Y') }}
                </h2>
            </div>
        </x-filament::card>

        <style>
            .table-container {
                overflow-x: auto;
                overflow-y: auto;
                max-width: 100%;
                max-height: 70vh;
                position: relative;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                /* rounded-xl */
                background: #ffffff;
                box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.06);
            }

            .sticky-table {
                border-collapse: separate;
                border-spacing: 0;
                width: 100%;
                table-layout: fixed;
                min-width: max-content;
                font-size: 12px;
                color: #111827;
                /* gray-900 */
            }

            .sticky-table th,
            .sticky-table td {
                border-bottom: 1px solid #eef2f7;
                /* light divider */
                padding: 10px 12px;
                white-space: nowrap;
                background: #ffffff;
            }

            /* Header sticky dengan gradient & shadow */
            .sticky-table thead th {
                position: sticky;
                top: 0;
                z-index: 20;
                background: linear-gradient(180deg, #f3f4f6 0%, #e5e7eb 100%);
                /* gray-100 -> gray-200 */
                font-weight: 700;
                text-align: center;
                color: #111827;
                backdrop-filter: saturate(1.2) blur(2px);
                box-shadow: inset 0 -1px 0 #e5e7eb, 0 2px 0 rgb(0 0 0 / 0.02);
            }

            /* Kolom pertama sticky kiri dengan pemisah halus */
            .sticky-col-1 {
                position: sticky;
                left: 0;
                z-index: 25;
                background: #ffffff;
                box-shadow: 2px 0 0 0 #eef2f7;
                /* garis pemisah kanan */
            }

            thead th.sticky-col-1 {
                z-index: 30;
                background: linear-gradient(180deg, #f3f4f6 0%, #e5e7eb 100%);
            }

            /* Zebra & hover halus */
            .sticky-table tbody tr:nth-child(even) td {
                background-color: #fafafa;
            }

            .sticky-table tbody tr:hover td {
                background-color: #f5f7fb;
            }

            .sticky-table tbody tr:nth-child(even) td.sticky-col-1 {
                background-color: #fafafa;
            }

            .sticky-table tbody tr:hover td.sticky-col-1 {
                background-color: #f5f7fb;
            }

            /* Weekend tint */
            .is-weekend td {
                background: #fff7ed;
            }

            /* amber-50 */
            .is-weekend td.sticky-col-1 {
                background: #fff7ed;
            }

            /* Sel tanggal */
            .date-cell .day-name {
                color: #6b7280;
                font-weight: 500;
                font-size: 11px;
            }

            .date-cell .day-number {
                color: #111827;
                font-weight: 700;
                font-size: 13px;
                line-height: 1;
            }

            /* Chip status */
            .status-chip {
                display: inline-flex;
                align-items: center;
                border-radius: 0.375rem;
                padding: 2px 6px;
                font-size: 10px;
                font-weight: 700;
                border: 1px solid;
                text-transform: uppercase;
                letter-spacing: .02em;
            }

            .status-masuk {
                background: #ecfdf5;
                color: #047857;
                border-color: #bbf7d0;
            }

            .status-sakit {
                background: #eff6ff;
                color: #1d4ed8;
                border-color: #bfdbfe;
            }

            .status-izin {
                background: #fffbeb;
                color: #b45309;
                border-color: #fde68a;
            }

            .status-halfday {
                background: #fffbeb;
                color: #854d0e;
                border-color: #fde68a;
            }

            .status-cuti {
                background: #ecfeff;
                color: #0e7490;
                border-color: #a5f3fc;
            }

            .status-alfa {
                background: #fef2f2;
                color: #b91c1c;
                border-color: #fecaca;
            }

            .status-default {
                background: #f9fafb;
                color: #374151;
                border-color: #e5e7eb;
            }

            /* Jam kecil */
            .time-text {
                margin-top: 2px;
                font-size: 10px;
                color: #4b5563;
            }

            .time-text .value {
                font-weight: 600;
                color: #111827;
            }

            /* Header staff name */
            .staff-header {
                max-width: 220px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            /* Badge container */
            .badge-container {
                display: flex;
                flex-wrap: wrap;
                gap: 2px;
                margin-top: 4px;
                justify-content: center;
            }

            /* Badge styles */
            .info-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 18px;
                height: 18px;
                padding: 0 6px;
                border-radius: 9999px;
                font-size: 10px;
                font-weight: 700;
                color: white;
                position: relative;
                cursor: help;
                transition: all 0.2s ease;
            }

            .info-badge:hover {
                transform: scale(1.1);
            }

            .badge-late {
                background: #ef4444;
                /* red-500 */
            }

            .badge-visit-solo {
                background: #3b82f6;
                /* blue-500 */
            }

            .badge-visit-luar {
                background: #10b981;
                /* emerald-500 */
            }

            /* Tooltip styles */
            .tooltip {
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                background: #1f2937;
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 10px;
                white-space: nowrap;
                z-index: 1000;
                opacity: 0;
                visibility: hidden;
                transition: all 0.2s ease;
                margin-bottom: 4px;
            }

            .tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                border: 4px solid transparent;
                border-top-color: #1f2937;
            }

            .info-badge:hover .tooltip {
                opacity: 1;
                visibility: visible;
            }
        </style>

        <div class="table-container">
            <table class="sticky-table">
                <thead>
                    <tr>
                        <th class="sticky-col-1 w-44 text-left">Tanggal</th>
                        @php
                            $staff = \App\Models\Staff::where('is_active', true)->orderBy('name')->get();
                        @endphp
                        @foreach ($staff as $staffMember)
                            <th class="min-w-40">
                                <div class="staff-header">{{ $staffMember->name }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
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
                        <tr class="{{ $isWeekend ? 'is-weekend' : '' }}">
                            <td class="sticky-col-1 w-44 date-cell text-center">
                                <div class="day-name">{{ $dayName }}</div>
                                <div class="day-number">{{ $dayNumber }}</div>
                            </td>
                            @foreach ($staff as $staffMember)
                                @php $attendance = $attendanceData->get($date)?->get($staffMember->id); @endphp
                                <td class="text-center align-top">
                                    @if ($attendance)
                                        @php
                                            $attendance = $attendance->first();
                                            $jamMasuk = $attendance->jam_masuk
                                                ? \Carbon\Carbon::parse($attendance->jam_masuk)->format('H:i')
                                                : '-';
                                            $jamPulang = $attendance->jam_pulang
                                                ? \Carbon\Carbon::parse($attendance->jam_pulang)->format('H:i')
                                                : '-';
                                            $statusClass = match ($attendance->status) {
                                                'masuk' => 'status-masuk',
                                                'sakit' => 'status-sakit',
                                                'izin' => 'status-izin',
                                                'halfday' => 'status-halfday',
                                                'cuti' => 'status-cuti',
                                                'alfa' => 'status-alfa',
                                                default => 'status-default',
                                            };
                                        @endphp
                                        <div class="status-chip {{ $statusClass }}">{{ $attendance->status }}</div>
                                        <div class="time-text">Masuk: <span class="value">{{ $jamMasuk }}</span>
                                        </div>
                                        <div class="time-text">Pulang: <span class="value">{{ $jamPulang }}</span>
                                        </div>

                                        <!-- Badge container untuk informasi tambahan -->
                                        <div class="badge-container">
                                            @if ($attendance->is_late)
                                                <div class="info-badge badge-late">
                                                    !
                                                    <div class="tooltip">{{ $attendance->keterangan ?: 'Terlambat' }}
                                                    </div>
                                                </div>
                                            @endif

                                            @if (!empty($attendance->visit_solo_count) && (int) $attendance->visit_solo_count > 0)
                                                <div class="info-badge badge-visit-solo">
                                                    {{ (int) $attendance->visit_solo_count }}
                                                    <div class="tooltip">{{ $attendance->keterangan ?: 'Visit Solo' }}
                                                    </div>
                                                </div>
                                            @endif

                                            @if (!empty($attendance->visit_luar_solo_count) && (int) $attendance->visit_luar_solo_count > 0)
                                                <div class="info-badge badge-visit-luar">
                                                    {{ (int) $attendance->visit_luar_solo_count }}
                                                    <div class="tooltip">
                                                        {{ $attendance->keterangan ?: 'Visit Luar Solo' }}</div>
                                                </div>
                                            @endif
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
    </div>
</x-filament-panels::page>
