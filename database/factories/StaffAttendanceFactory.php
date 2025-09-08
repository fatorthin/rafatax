<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<\App\Models\StaffAttendance>
 */
class StaffAttendanceFactory extends Factory
{
    protected $model = StaffAttendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Jam kerja normal 08:00 - 17:30
        $jamMasukBase = '08:00:00';
        $jamPulangBase = '17:30:00';

        // Sedikit variasi keterlambatan acak (0-20 menit)
        $lateMinutes = Arr::random([0, 0, 0, 5, 10, 15, 20]);
        $isLate = $lateMinutes > 0;

        $jamMasuk = \Carbon\Carbon::createFromFormat('H:i:s', $jamMasukBase)
            ->addMinutes($lateMinutes)
            ->format('H:i:s');

        // Sedikit variasi pulang (0-30 menit setelah 17:30)
        $overtimeMinutes = Arr::random([0, 0, 0, 15, 30]);
        $jamPulang = \Carbon\Carbon::createFromFormat('H:i:s', $jamPulangBase)
            ->addMinutes($overtimeMinutes)
            ->format('H:i:s');

        // Hitung durasi lembur (jam) hanya jika pulang setelah 17:30
        $durasiLembur = max(0, $overtimeMinutes / 60);

        // Pilih staff id yang ada
        $staffId = Staff::query()->inRandomOrder()->value('id');

        // Status acak
        $status = Arr::random(['masuk', 'sakit', 'izin', 'cuti', 'alfa', 'halfday']);

        // Kunjungan acak 1-3
        $visitSolo = $this->faker->numberBetween(1, 3);
        $visitLuarSolo = $this->faker->numberBetween(1, 3);

        return [
            'staff_id' => $staffId ?? Staff::factory(),
            'tanggal' => $this->faker->date(),
            'status' => $status,
            'is_late' => $isLate,
            'visit_solo_count' => $visitSolo,
            'visit_luar_solo_count' => $visitLuarSolo,
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'durasi_lembur' => $durasiLembur,
            'keterangan' => null,
        ];
    }

    /**
     * State helper untuk tanggal tertentu.
     */
    public function onDate(string $date): self
    {
        return $this->state(fn () => [
            'tanggal' => $date,
        ]);
    }

    /**
     * State helper untuk staff tertentu.
     */
    public function forStaff(int $staffId): self
    {
        return $this->state(fn () => [
            'staff_id' => $staffId,
        ]);
    }
}
