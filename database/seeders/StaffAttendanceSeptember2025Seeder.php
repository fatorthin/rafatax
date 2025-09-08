<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffAttendanceSeptember2025Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $start = Carbon::create(2025, 9, 1);
        $end = Carbon::create(2025, 9, 30);

        $dates = [];
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            // 1 = Monday ... 5 = Friday
            if ($cursor->isWeekday() && $cursor->dayOfWeekIso <= 5) {
                $dates[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        $staffIds = Staff::query()->pluck('id');
        if ($staffIds->isEmpty()) {
            $this->command?->warn('Tidak ada staff. Seeder berhenti.');
            return;
        }

        DB::transaction(function () use ($dates, $staffIds) {
            foreach ($staffIds as $staffId) {
                foreach ($dates as $date) {
                    // Hindari duplikasi
                    $exists = StaffAttendance::query()
                        ->where('staff_id', $staffId)
                        ->where('tanggal', $date)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    StaffAttendance::factory()
                        ->forStaff($staffId)
                        ->onDate($date)
                        ->create();
                }
            }
        });

        $this->command?->info('Data presensi bulan September 2025 (Senin–Jumat) berhasil dibuat untuk semua staff.');
    }
}
