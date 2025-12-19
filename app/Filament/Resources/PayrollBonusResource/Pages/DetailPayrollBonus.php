<?php

namespace App\Filament\Resources\PayrollBonusResource\Pages;

use Filament\Tables\Table;
use App\Models\CaseProject;
use App\Models\PayrollBonus;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use App\Models\CaseProjectDetail;
use App\Models\PayrollBonusDetail;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\PayrollBonusResource;
use App\Models\Staff;
use App\Services\WablasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\Summarizers\Summarizer;

class DetailPayrollBonus extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PayrollBonusResource::class;

    protected static string $view = 'filament.resources.payroll-bonus-resource.pages.detail-payroll-bonus';

    public PayrollBonus $record;

    public function getTitle(): string
    {
        return 'Detail Payroll Bonus - ' . $this->record->description;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit Data Payroll Bonus')
                ->record($this->record)
                ->form([
                    \Filament\Forms\Components\TextInput::make('description')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\Select::make('case_project_ids')
                        ->label('Case Project')
                        ->options(\App\Models\CaseProject::pluck('description', 'id'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('payroll_date')
                        ->required(),
                ]),
            Action::make('send_all_wablas')
                ->label('Kirim Semua Slip WA')
                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Kirim Semua Slip Bonus via WhatsApp')
                ->modalDescription('Apakah Anda yakin ingin mengirim semua slip bonus ke staff terkait?')
                ->action(function (WablasService $wablasService) {
                    $details = PayrollBonusDetail::where('payroll_bonus_id', $this->record->id)->get();
                    $successCount = 0;
                    $failCount = 0;

                    foreach ($details as $record) {
                        $result = $this->sendBonusWablas($record, $wablasService);
                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $failCount++;
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Proses Selesai')
                        ->body("Berhasil: {$successCount}, Gagal: {$failCount}")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Informasi payroll Bonus')
                    ->schema([
                        TextEntry::make('description')->label('Deskripsi'),
                        TextEntry::make('payroll_date')->label('Tanggal Payroll')->date('d-m-Y'),
                        TextEntry::make('total_amount')->label('Total Amount (Rp)')
                            ->state(function () {
                                $total = PayrollBonusDetail::where('payroll_bonus_id', $this->record->id)
                                    ->sum('amount');
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            }),
                        TextEntry::make('case_projects')->label('Case Projects')
                            ->state(function () {
                                // Ambil daftar case_project_ids dengan aman (tangani kemungkinan string JSON)
                                $ids = $this->record->case_project_ids ?? [];
                                if (!is_array($ids)) {
                                    $decoded = json_decode((string) $ids, true);
                                    $ids = is_array($decoded) ? $decoded : [];
                                }

                                if (empty($ids)) {
                                    return '-';
                                }

                                // Ambil description dan company_name dari CaseProject dengan relasi client
                                $projects = CaseProject::with('client')
                                    ->whereIn('id', $ids)
                                    ->orderBy('case_date')
                                    ->get()
                                    ->map(function ($project) {
                                        $companyName = $project->client->company_name ?? 'N/A';
                                        return $project->description . ' (' . $companyName . ')';
                                    })
                                    ->toArray();

                                return implode(', ', $projects);
                            }),
                    ])->columns(4)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PayrollBonusDetail::query()->where('payroll_bonus_id', $this->record->id))
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->state(fn($record, $rowLoop) => $rowLoop->iteration)
                    ->sortable(),

                TextColumn::make('staff.name')
                    ->label('Nama Staff')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Total Bonus (Rp)')
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Bonus:')
                            ->using(function ($query) {
                                $total = $query->sum('amount');
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            })
                    ),
            ])
            ->paginated(false)
            ->striped()
            ->actions([
                \Filament\Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail Bonus')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Detail Bonus - ' . $record->staff->name)
                    ->modalContent(function ($record) {
                        // Pastikan nilai berupa array meskipun data lama tersimpan sebagai string JSON
                        $caseProjectDetailIds = $record->case_project_detail_ids ?? [];
                        if (!is_array($caseProjectDetailIds)) {
                            $decoded = json_decode((string) $caseProjectDetailIds, true);
                            $caseProjectDetailIds = is_array($decoded) ? $decoded : [];
                        }
                        $caseProjectDetails = CaseProjectDetail::with(['caseProject', 'caseProject.client'])
                            ->whereIn('id', $caseProjectDetailIds)
                            ->get();

                        return view('filament.modals.payroll-bonus-detail', [
                            'record' => $record,
                            'caseProjectDetails' => $caseProjectDetails,
                        ]);
                    })
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                \Filament\Tables\Actions\Action::make('send_wablas')
                    ->label('Kirim Slip WA')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (PayrollBonusDetail $record, WablasService $wablasService) {
                        $result = $this->sendBonusWablas($record, $wablasService);

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),

                \Filament\Tables\Actions\Action::make('download_slip')
                    ->label('Download Slip')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(fn($record) => route('exports.payroll-bonus.slip', ['detail' => $record->id]))
                    ->openUrlInNewTab(false),
            ]);
    }
    private function sendBonusWablas(PayrollBonusDetail $record, WablasService $wablasService): array
    {
        try {
            $staff = Staff::find($record->staff_id);
            if (!$staff || !$staff->phone) {
                return ['success' => false, 'message' => 'Nomor telepon staff tidak ditemukan.'];
            }

            $payrollDate = $this->record->payroll_date ? \Carbon\Carbon::parse($this->record->payroll_date)->format('F Y') : '-';
            $amount = number_format($record->amount, 0, ',', '.');

            // Generate Message Content
            $message = "📋 *SLIP PAYROLL BONUS RAFATAX*\n\n";
            $message .= "👤 *Nama*: {$staff->name}\n";
            $message .= "📅 *Periode*: {$payrollDate}\n";
            $message .= "💰 *Total Bonus*: Rp {$amount}\n\n";

            $message .= "*Rincian Project:*\n";
            $caseProjectDetailIds = $record->case_project_detail_ids ?? [];
            if (!is_array($caseProjectDetailIds)) {
                $caseProjectDetailIds = json_decode((string) $caseProjectDetailIds, true) ?? [];
            }

            $projectDetails = CaseProjectDetail::with(['caseProject', 'caseProject.client'])->whereIn('id', $caseProjectDetailIds)->get();
            foreach ($projectDetails as $detail) {
                $projectName = $detail->caseProject->description ?? 'Unknown Project';
                $bonusVal = number_format($detail->bonus, 0, ',', '.');
                $message .= "- {$projectName}: Rp {$bonusVal}\n";
            }

            $message .= "\n📄 Slip bonus detail dalam bentuk PDF akan dikirim setelah pesan ini.\n";
            $message .= "Terima kasih atas dedikasi dan kerja kerasnya! 🙏";

            // Generate PDF
            $data = [
                'detail' => $record,
                'caseProjectDetails' => $projectDetails,
            ];
            $pdf = Pdf::loadView('exports.payroll-bonus-slip', $data);

            // Save to temp
            $filename = 'Slip_Bonus_' . str_replace(' ', '_', $staff->name) . '_' . time() . '.pdf';
            $tempPath = storage_path('app/temp/' . $filename);
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            $pdf->save($tempPath);

            // Send Message
            $wablasService->sendMessage($staff->phone, $message);

            // Send Document
            $result = $wablasService->sendDocument($staff->phone, $tempPath, "📄 Slip Bonus {$staff->name} - {$payrollDate}");

            // Cleanup
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return [
                'success' => $result['status'] ?? false,
                'message' => ($result['status'] ?? false) ? "Slip bonus berhasil dikirim ke {$staff->name}" : "Gagal mengirim dokumen: " . ($result['message'] ?? 'Unknown error')
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
