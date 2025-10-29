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
                ->modalDescription('Apakah Anda yakin ingin mengirim slip gaji PDF ke semua staff yang memiliki nomor WhatsApp?')
                ->modalSubmitActionLabel('Kirim Semua PDF')
                ->action(function () {
                    $details = PayrollDetail::with('staff')
                        ->where('payroll_id', $this->record->id)
                        ->whereHas('staff', function ($query) {
                            $query->whereNotNull('phone')->where('phone', '!=', '');
                        })
                        ->get();

                    $successCount = 0;
                    $failCount = 0;

                    foreach ($details as $detail) {
                        try {
                            // Panggil controller langsung
                            $controller = new \App\Http\Controllers\PayrollWhatsAppController(
                                new \App\Services\WablasService()
                            );

                            $response = $controller->sendPayslipWithPdf($detail);
                            $data = $response->getData(true);

                            if ($data['success']) {
                                $successCount++;
                            } else {
                                $failCount++;
                            }

                            // Delay 2 detik antar pengiriman untuk menghindari rate limit
                            sleep(2);
                        } catch (\Exception $e) {
                            $failCount++;
                        }
                    }

                    Notification::make()
                        ->title('Selesai!')
                        ->body("Berhasil mengirim {$successCount} slip gaji PDF. Gagal: {$failCount}")
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
