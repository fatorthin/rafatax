<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use App\Filament\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayrolls extends ManageRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_thr')
                ->label('Tambah Payroll THR')
                ->icon('heroicon-o-gift')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('payroll_date')
                        ->label('Tanggal Payroll')
                        ->required(),
                    \Filament\Forms\Components\Repeater::make('thr_data')
                        ->label('Data THR Staff')
                        ->schema([
                            \Filament\Forms\Components\Select::make('staff_id')
                                ->label('Staff')
                                ->options(\App\Models\Staff::pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            \Filament\Forms\Components\TextInput::make('salary')
                                ->label('Nominal THR')
                                ->numeric()
                                ->required(),
                        ])
                        ->columns(2)
                ])
                ->action(function (array $data) {
                    $payroll = \App\Models\Payroll::create([
                        'name' => 'Payroll THR ' . \Carbon\Carbon::parse($data['payroll_date'])->translatedFormat('F Y'),
                        'payroll_date' => $data['payroll_date'],
                    ]);

                    foreach ($data['thr_data'] as $thr) {
                        \App\Models\PayrollDetail::create([
                            'payroll_id' => $payroll->id,
                            'staff_id' => $thr['staff_id'],
                            'salary' => $thr['salary'],
                            'bonus_position' => 0,
                            'bonus_competency' => 0,
                            'overtime_count' => 0,
                            'visit_solo_count' => 0,
                            'visit_luar_solo_count' => 0,
                            'sick_leave_count' => 0,
                            'halfday_count' => 0,
                            'leave_count' => 0,
                            'cuti_count' => 0,
                            'cut_bpjs_kesehatan' => 0,
                            'cut_bpjs_ketenagakerjaan' => 0,
                            'cut_lain' => 0,
                            'cut_hutang' => 0,
                            'bonus_lain' => 0,
                            'overtime_multiplier' => 0,
                        ]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Payroll THR Berhasil Ditambahkan')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make()
                ->label('Tambah Payroll Baru')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }
}
