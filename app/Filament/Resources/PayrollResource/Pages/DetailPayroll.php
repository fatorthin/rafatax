<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use Carbon\Carbon;
use App\Models\Staff;
use Filament\Actions;
use App\Models\Payroll;
use Filament\Tables\Table;
use App\Models\PayrollDetail;
use App\Models\StaffAttendance;
use App\Models\StaffCompetency;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\PayrollResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;
use App\Jobs\SendPayslipPdf;
use Illuminate\Support\Facades\Log;

class DetailPayroll extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PayrollResource::class;

    protected static string $view = 'filament.resources.payroll-resource.pages.detail-payroll';

    public Payroll $record;

    public function getTitle(): string
    {
        return 'Detail Payroll - ' . $this->record->name;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Informasi Payroll')
                    ->schema([
                        TextEntry::make('name')->label('Nama'),
                        TextEntry::make('payroll_date')->label('Periode')->date('F Y'),
                        TextEntry::make('created_at')->label('Dibuat')->dateTime('d-m-Y H:i'),
                        TextEntry::make('total_sick')
                            ->label('Total Sakit')
                            ->state(fn() => (int) PayrollDetail::where('payroll_id', $this->record->id)->sum('sick_leave_count')),
                        TextEntry::make('total_halfday')
                            ->label('Total Halfday')
                            ->state(fn() => (int) PayrollDetail::where('payroll_id', $this->record->id)->sum('halfday_count')),
                        TextEntry::make('total_leave')
                            ->label('Total Ijin/Alfa')
                            ->state(fn() => (int) PayrollDetail::where('payroll_id', $this->record->id)->sum('leave_count')),
                        TextEntry::make('total_cuti')
                            ->label('Total Cuti')
                            ->state(fn() => (int) PayrollDetail::where('payroll_id', $this->record->id)->sum('cuti_count')),
                        TextEntry::make('grand_total_salary')
                            ->label('Total Gaji Dibayar')
                            ->state(function () {
                                $details = PayrollDetail::where('payroll_id', $this->record->id)->get();
                                $sum = $details->sum(function ($d) {
                                    $bonusLembur = $d->overtime_count * 10000;
                                    $bonusVisitSolo = $d->visit_solo_count * 10000;
                                    $bonusVisitLuar = $d->visit_luar_solo_count * 15000;
                                    $cutSakit = $d->sick_leave_count * 0.5 * $d->salary / 25;
                                    $cutHalfday = $d->halfday_count * 0.5 * $d->salary / 25;
                                    $cutIjin = $d->leave_count * $d->salary / 25;
                                    return $d->salary + $d->bonus_position + $d->bonus_competency + $bonusLembur + $bonusVisitSolo + $bonusVisitLuar + $d->bonus_lain - $d->cut_bpjs_kesehatan - $d->cut_bpjs_ketenagakerjaan - $d->cut_lain - $d->cut_hutang - $cutSakit - $cutHalfday - $cutIjin;
                                });
                                return 'Rp ' . number_format($sum, 0, ',', '.');
                            }),
                    ])->columns(3)
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => route('exports.payroll.excel', ['payroll' => $this->record->id]))
                ->openUrlInNewTab(false),
            Actions\Action::make('cut_off')
                ->label('Cut Off Payroll')
                ->icon('heroicon-o-scissors')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    $payroll = $this->record;
                    $periode = Carbon::parse($payroll->payroll_date);
                    $year = (int) $periode->format('Y');
                    $month = (int) $periode->format('m');

                    DB::transaction(function () use ($payroll, $year, $month) {
                        PayrollDetail::where('payroll_id', $payroll->id)->delete();

                        $staffList = Staff::where('is_active', true)->get();

                        foreach ($staffList as $staff) {
                            $salary = (float) ($staff->salary ?? 0);
                            $bonusPosition = (float) optional($staff->positionReference)->salary ?? 0;

                            $validCompetencyCount = StaffCompetency::where('staff_id', $staff->id)
                                ->whereDate('date_of_expiry', '>=', Carbon::create($year, $month, 1)->endOfMonth()->toDateString())
                                ->count();
                            $bonusCompetency = (int) $validCompetencyCount * 30000;

                            $attendanceQuery = StaffAttendance::where('staff_id', $staff->id)
                                ->whereYear('tanggal', $year)
                                ->whereMonth('tanggal', $month);

                            $overtimeCount = (float) (clone $attendanceQuery)->sum('durasi_lembur');
                            $visitSoloCount = (int) (clone $attendanceQuery)->sum('visit_solo_count');
                            $visitLuarSoloCount = (int) (clone $attendanceQuery)->sum('visit_luar_solo_count');
                            $sickLeaveCount = (int) (clone $attendanceQuery)->where('status', 'sakit')->count();
                            $halfdayCount = (int) (clone $attendanceQuery)->where('status', 'halfday')->count();
                            $leaveCount = (int) (clone $attendanceQuery)->whereIn('status', ['izin', 'alfa'])->count();
                            $cutiCount = (int) (clone $attendanceQuery)->whereIn('status', ['cuti'])->count();

                            PayrollDetail::create([
                                'payroll_id' => $payroll->id,
                                'staff_id' => $staff->id,
                                'salary' => $salary,
                                'bonus_position' => $bonusPosition,
                                'bonus_competency' => $bonusCompetency,
                                'overtime_count' => $overtimeCount,
                                'visit_solo_count' => $visitSoloCount,
                                'visit_luar_solo_count' => $visitLuarSoloCount,
                                'sick_leave_count' => $sickLeaveCount,
                                'halfday_count' => $halfdayCount,
                                'leave_count' => $leaveCount,
                                'cuti_count' => $cutiCount,
                                'cut_bpjs_kesehatan' => 0,
                                'cut_bpjs_ketenagakerjaan' => 0,
                                'cut_lain' => 0,
                                'cut_hutang' => 0,
                                'bonus_lain' => 0,
                            ]);
                        }
                    });

                    Notification::make()
                        ->title('Cut off payroll berhasil diperbarui')
                        ->success()
                        ->send();

                    $this->redirect(PayrollResource::getUrl('detail', ['record' => $this->record]));
                }),
            Actions\Action::make('send_all_whatsapp_pdf')
                ->label('Kirim Semua PDF ke WA')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Kirim Semua Slip Gaji PDF via WhatsApp')
                ->modalDescription('Apakah Anda yakin ingin mengirim slip gaji PDF ke semua staff yang memiliki nomor WhatsApp?')
                ->modalSubmitActionLabel('Kirim Semua PDF')
                ->action(function () {
                    // Ambil semua detail yang memiliki nomor WA
                    $details = PayrollDetail::with('staff')
                        ->where('payroll_id', $this->record->id)
                        ->whereHas('staff', function ($query) {
                            $query->whereNotNull('phone')->where('phone', '!=', '');
                        })
                        ->get();

                    if ($details->isEmpty()) {
                        Notification::make()
                            ->title('Tidak ada data untuk dikirim')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Siapkan jobs per staff agar proses berjalan di background (menghindari timeout)
                    $jobs = [];
                    foreach ($details as $detail) {
                        $jobs[] = new SendPayslipPdf($detail->id);
                    }

                    // Beri peringatan jika queue masih sync (berpotensi timeout)
                    if (config('queue.default') === 'sync') {
                        Notification::make()
                            ->title('Peringatan: Queue belum dikonfigurasi')
                            ->body('Saat ini koneksi queue adalah "sync" sehingga proses kirim massal tetap berjalan di request dan berpotensi timeout. Disarankan set QUEUE_CONNECTION=database dan jalankan worker.')
                            ->warning()
                            ->send();
                    }

                    // Dispatch sebagai batch agar lebih mudah dimonitor di log
                    Bus::batch($jobs)
                        ->name('Kirim semua slip gaji PDF: ' . $this->record->name)
                        ->onQueue('whatsapp')
                        ->allowFailures()
                        ->then(function (Batch $batch) {
                            Log::info('Batch kirim slip gaji PDF selesai', [
                                'batch_id' => $batch->id,
                                'total_jobs' => $batch->totalJobs,
                                'failed_jobs' => $batch->failedJobs,
                            ]);
                        })
                        ->dispatch();

                    // Beri notifikasi segera, proses jalan di background
                    Notification::make()
                        ->title('Pengiriman dimulai')
                        ->body('Pengiriman slip gaji PDF ke WhatsApp sedang diproses di background untuk ' . $details->count() . ' staff. Anda dapat menutup halaman ini.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PayrollDetail::query()->where('payroll_id', $this->record->id))
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->state(fn($record, $rowLoop) => $rowLoop->iteration)
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('staff.name')
                    ->label('Nama Staff')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('salary')
                    ->label('Gaji Pokok')
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('bonus_position')
                    ->label('TUNJAB')
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('bonus_competency')
                    ->label('TUNKOMP')
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('sick_leave_count')
                    ->label('Sakit')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('halfday_count')
                    ->label('Tengah Hari')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('leave_count')
                    ->label('Ijin/Alfa')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('cuti_count')
                    ->label('Cuti')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('overtime_count')
                    ->label('Lembur')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('visit_solo_count')
                    ->label('T. Solo')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('visit_luar_solo_count')
                    ->label('T. Luar Solo')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('bonus_lembur')
                    ->label('Bonus Lembur')
                    ->getStateUsing(fn($record) => $record->overtime_count * 10000)
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('bonus_visit_solo')
                    ->label('Bonus Visit Solo')
                    ->getStateUsing(fn($record) => $record->visit_solo_count * 10000)
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('bonus_visit_luar_solo')
                    ->label('Bonus Visit Luar Solo')
                    ->getStateUsing(fn($record) => $record->visit_luar_solo_count * 15000)
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextInputColumn::make('bonus_lain')
                    ->label('Bonus Lain')
                    ->alignEnd()
                    ->rules(['numeric'])
                    ->sortable(),

                TextInputColumn::make('cut_bpjs_kesehatan')
                    ->label('Pot. BPJS Kesehatan')
                    ->alignEnd()
                    ->rules(['numeric'])
                    ->sortable(),

                TextInputColumn::make('cut_bpjs_ketenagakerjaan')
                    ->label('Pot. BPJS Ketenagakerjaan')
                    ->alignEnd()
                    ->rules(['numeric'])
                    ->sortable(),

                TextColumn::make('cut_sakit')
                    ->label('Pot. Sakit')
                    ->getStateUsing(fn($record) => $record->sick_leave_count * 0.5 * $record->salary / 25)
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('cut_tengah_hari')
                    ->label('Pot. Tengah Hari')
                    ->getStateUsing(fn($record) => $record->halfday_count * 0.5 * $record->salary / 25)
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('cut_ijin')
                    ->label('Pot. Ijin')
                    ->getStateUsing(fn($record) => $record->leave_count * $record->salary / 25)
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextInputColumn::make('cut_lain')
                    ->label('Pot. Lain')
                    ->alignEnd()
                    ->rules(['numeric'])
                    ->sortable(),

                TextInputColumn::make('cut_hutang')
                    ->label('Pot. Hutang')
                    ->alignEnd()
                    ->rules(['numeric'])
                    ->sortable(),

                TextColumn::make('total_bonus')
                    ->label('Total Bonus')
                    ->getStateUsing(fn($record) => ($record->overtime_count * 10000) + ($record->visit_solo_count * 10000) + ($record->visit_luar_solo_count * 15000) + $record->bonus_lain)
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('total_cut')
                    ->label('Total Pot.')
                    ->getStateUsing(fn($record) => $record->cut_bpjs_kesehatan + $record->cut_bpjs_ketenagakerjaan + $record->cut_lain + $record->cut_hutang + ($record->sick_leave_count * 0.5 * $record->salary / 25) + ($record->halfday_count * 0.5 * $record->salary / 25) + ($record->leave_count * $record->salary / 25))
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('total_salary')
                    ->label('Total Salary')
                    ->getStateUsing(fn($record) => $record->salary + $record->bonus_position + $record->bonus_competency + ($record->overtime_count * 10000) + ($record->visit_solo_count * 10000) + ($record->visit_luar_solo_count * 15000) + $record->bonus_lain - $record->cut_bpjs_kesehatan - $record->cut_bpjs_ketenagakerjaan - $record->cut_lain - $record->cut_hutang - ($record->sick_leave_count * 0.5 * $record->salary / 25) - ($record->halfday_count * 0.5 * $record->salary / 25) - ($record->leave_count * $record->salary / 25))
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Summarizer::make()
                            ->label('Total:')
                            ->using(function ($query) {
                                $records = $query->get();
                                $total = $records->sum(function ($record) {
                                    return $record->salary + $record->bonus_position + $record->bonus_competency + ($record->overtime_count * 10000) + ($record->visit_solo_count * 10000) + ($record->visit_luar_solo_count * 15000) + $record->bonus_lain - $record->cut_bpjs_kesehatan - $record->cut_bpjs_ketenagakerjaan - $record->cut_lain - $record->cut_hutang - ($record->sick_leave_count * 0.5 * $record->salary / 25) - ($record->halfday_count * 0.5 * $record->salary / 25) - ($record->leave_count * $record->salary / 25);
                                });
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            })
                    ),
            ])
            ->paginated(false)
            ->striped()
            ->actions([
                \Filament\Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn($record) => route('filament.admin.resources.payroll-details.edit', ['record' => $record->id]))
                    ->openUrlInNewTab(false),

                \Filament\Tables\Actions\Action::make('slip_pdf')
                    ->label('Slip PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn($record) => route('exports.payroll.payslip', ['detail' => $record->id]))
                    ->openUrlInNewTab(false),

                \Filament\Tables\Actions\Action::make('send_whatsapp')
                    ->label('Kirim WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Slip Gaji via WhatsApp')
                    ->modalDescription(fn($record) => "Apakah Anda yakin ingin mengirim slip gaji untuk {$record->staff->name} ke nomor {$record->staff->phone}?")
                    ->modalSubmitActionLabel('Kirim')
                    ->action(function ($record) {
                        try {
                            // Panggil controller langsung
                            $controller = new \App\Http\Controllers\PayrollWhatsAppController(
                                new \App\Services\WablasService()
                            );

                            $response = $controller->sendPayslip($record);
                            $data = $response->getData(true);

                            if ($data['success']) {
                                Notification::make()
                                    ->title('Berhasil!')
                                    ->body('Slip gaji berhasil dikirim ke WhatsApp')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Gagal!')
                                    ->body($data['message'])
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error!')
                                ->body('Terjadi kesalahan saat mengirim slip gaji: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn($record) => !empty($record->staff->phone)),

                \Filament\Tables\Actions\Action::make('send_whatsapp_pdf')
                    ->label('Kirim PDF ke WA')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Slip Gaji PDF via WhatsApp')
                    ->modalDescription(fn($record) => "Apakah Anda yakin ingin mengirim slip gaji PDF untuk {$record->staff->name} ke nomor {$record->staff->phone}?")
                    ->modalSubmitActionLabel('Kirim PDF')
                    ->action(function ($record) {
                        try {
                            // Panggil controller langsung
                            $controller = new \App\Http\Controllers\PayrollWhatsAppController(
                                new \App\Services\WablasService()
                            );

                            $response = $controller->sendPayslipWithPdf($record);
                            $data = $response->getData(true);

                            if ($data['success']) {
                                Notification::make()
                                    ->title('Berhasil!')
                                    ->body('Slip gaji PDF berhasil dikirim ke WhatsApp')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Gagal!')
                                    ->body($data['message'])
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error!')
                                ->body('Terjadi kesalahan saat mengirim slip gaji PDF: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn($record) => !empty($record->staff->phone)),
            ]);
    }
}
