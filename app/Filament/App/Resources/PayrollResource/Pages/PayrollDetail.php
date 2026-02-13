<?php

namespace App\Filament\App\Resources\PayrollResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use App\Filament\App\Resources\PayrollResource;

class PayrollDetail extends Page
{
    protected static string $resource = PayrollResource::class;

    protected static string $view = 'filament.app.resources.payroll-resource.pages.payroll-detail';

    public $record;

    public function mount($record): void
    {
        $this->record = PayrollResource::resolveRecordRouteBinding($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->icon('heroicon-o-arrow-left')
                ->url(PayrollResource::getUrl('index'))
                ->color('gray'),
            Actions\Action::make('send_all_whatsapp_pdf')
                ->label('Kirim Semua PDF ke WA')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Kirim Semua Slip Gaji PDF via WhatsApp')
                ->modalDescription('Apakah Anda yakin ingin mengirim slip gaji PDF ke semua staff yang memiliki nomor WhatsApp? Proses akan berjalan di background.')
                ->modalSubmitActionLabel('Kirim Semua PDF')
                ->action(function () {
                    $details = \App\Models\PayrollDetail::with('staff')
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

                    // Dispatch setiap pengiriman sebagai Job terpisah (background)
                    foreach ($details as $detail) {
                        \App\Jobs\SendPayslipPdf::dispatch($detail->id);
                    }

                    Notification::make()
                        ->title('Pengiriman Dijadwalkan')
                        ->body("{$details->count()} slip gaji akan dikirim via WhatsApp di background. Cek log untuk status pengiriman.")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Payroll Detail - ' . $this->record->name;
    }
}
