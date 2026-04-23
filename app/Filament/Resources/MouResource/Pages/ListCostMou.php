<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Models\Coa;
use App\Models\MoU;
use App\Models\CaseProject;
use Filament\Actions;
use Filament\Support\RawJs;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListMou;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Resources\MouResource;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\InvoiceResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use App\Filament\Resources\MouResource\Widgets\MouStatsOverview;

class ListCostMou extends Page implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string $resource = MouResource::class;

    protected static ?string $model = CostListMou::class;

    protected static string $view = 'filament.resources.mou-resource.pages.list-cost-mou';

    public MoU $mou;

    public $cost_lists;

    public $invoices;

    public $pendingChecklistIds = [];

    public function mount($record): void
    {
        $this->mou = MoU::findOrFail($record);

        // Self-healing: Reset checklist items linked to deleted invoices
        \App\Models\ChecklistMou::where('mou_id', $this->mou->id)
            ->whereNotNull('invoice_id')
            ->whereDoesntHave('invoice')
            ->update(['invoice_id' => null, 'status' => 'pending']);

        $this->cost_lists = CostListMou::where('mou_id', $record)->get();
        $this->invoices = Invoice::where('mou_id', $record)->get();
    }

    public function getTitle(): string
    {
        return 'Detail MoU #' . $this->mou->id;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MouStatsOverview::make([
                'mouId' => $this->mou->id,
            ]),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->mou)
            ->schema([
                Section::make('MoU Information')
                    ->schema([
                        TextEntry::make('mou_number')
                            ->label('MoU Number')
                            ->weight('bold'),
                        TextEntry::make('client.company_name')
                            ->label('Client')
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->weight('bold')
                            ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                        TextEntry::make('type')
                            ->label('Type')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'pt' => 'PT',
                                'kkp' => 'KKP',
                            })
                            ->weight('bold'),
                        TextEntry::make('start_date')
                            ->label('Tanggal Awal Pengerjaan')
                            ->weight('bold')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->locale('id')->translatedFormat('d F Y') : null),
                        TextEntry::make('end_date')
                            ->label('Tanggal Akhir Pengerjaan')
                            ->weight('bold')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->locale('id')->translatedFormat('d F Y') : null),
                        TextEntry::make('tahun_pajak')
                            ->label('Tahun Pajak')
                            ->weight('bold')
                            ->getStateUsing(fn($record) => $record->tahun_pajak ?: ($record->start_date ? \Carbon\Carbon::parse($record->start_date)->year : null)),
                        TextEntry::make('tanggal_tagih_awal')
                            ->label('Tanggal Tagih Awal')
                            ->weight('bold')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->locale('id')->translatedFormat('d F Y') : null),
                        TextEntry::make('tanggal_tagih_akhir')
                            ->label('Tanggal Tagih Akhir')
                            ->weight('bold')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->locale('id')->translatedFormat('d F Y') : null),
                        TextEntry::make('client.contact_person')
                            ->label('Contact Person')
                            ->weight('bold'),
                        TextEntry::make('client.phone')
                            ->label('Contact Number')
                            ->weight('bold'),
                        TextEntry::make('categoryMou.name')
                            ->label('Category MoU')
                            ->weight('bold'),
                        TextEntry::make('link_mou')
                            ->label('Link MoU')
                            ->url(fn($record) => $record->link_mou, shouldOpenInNewTab: true)
                            ->color('primary')
                            ->weight('bold')
                            ->default('-'),
                        TextEntry::make('discount_amount')
                            ->label('Discount Amount')
                            ->weight('bold')
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 0, ',', '.');
                            }),
                    ])
                    ->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => CostListMou::where('mou_id', $this->mou->id))
            ->heading('Cost List')
            ->description('Detail biaya untuk MoU ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('coa.name')->label('CoA'),
                TextColumn::make('description')->label('Description'),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(),
                TextColumn::make('satuan_quantity')
                    ->label('Satuan'),
                TextColumn::make('amount')
                    ->label('Price')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignEnd(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total Amount')),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->fillForm(function (CostListMou $record): array {
                        return [
                            'coa_id' => $record->coa_id,
                            'description' => $record->description,
                            'quantity' => $record->quantity,
                            'satuan_quantity' => $record->satuan_quantity,
                            'amount' => $record->amount,
                            'total_amount' => $record->total_amount,
                        ];
                    })
                    ->form([
                        Select::make('coa_id')
                            ->label('CoA')
                            ->options(Coa::whereIn('id', [119, 120, 121, 122, 123, 124, 125, 126])->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Qty')
                            ->default(1)
                            ->required()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->live(onBlur: true)
                            ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, ',', '.') : '1')
                            ->dehydrateStateUsing(fn($state) => (float) str_replace('.', '', $state))
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $qty = floatval(str_replace('.', '', $state ?? '0'));
                                $price = floatval(str_replace('.', '', $get('amount') ?? '0'));
                                $set('total_amount', number_format($qty * $price, 0, ',', '.'));
                            }),
                        TextInput::make('satuan_quantity')
                            ->label('Satuan'),
                        TextInput::make('amount')
                            ->label('Price')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->live(onBlur: true)
                            ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, ',', '.') : null)
                            ->dehydrateStateUsing(fn($state) => (float) str_replace('.', '', $state))
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $price = floatval(str_replace('.', '', $state ?? '0'));
                                $qty = floatval(str_replace('.', '', $get('quantity') ?? '1'));
                                $set('total_amount', number_format($qty * $price, 0, ',', '.'));
                            }),
                        TextInput::make('total_amount')
                            ->label('Total')
                            ->readOnly()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, ',', '.') : '0')
                            ->dehydrateStateUsing(fn($state) => (float) str_replace('.', '', $state)),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                    ])
                    ->modalWidth('lg'),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->makeEditMouAction()->label('Edit'),
            $this->makeAddCostListAction()->label('Add Cost'),
            $this->makeCreateCaseProjectAction(),
            Actions\ActionGroup::make([
                $this->makeCreateInvoiceAction()->label('New Invoice'),
                $this->makeCreateOldInvoiceAction()->label('Old Invoice'),
            ])
                ->label('Invoices')
                ->icon('heroicon-m-document-text')
                ->color('indigo')
                ->button(),
            Actions\ActionGroup::make([
                $this->makePreviewPdfAction()->label('Preview PDF'),
                $this->makeExportPdfAction()->label('Print PDF'),
                $this->makeSendMouWhatsappAction()->label('WhatsApp'),
            ])
                ->label('Export')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('success')
                ->button(),
            $this->makeBackAction()->label('Back'),
        ];
    }

    private function makeCreateCaseProjectAction(): Action
    {
        return Action::make('create_case_project')
            ->label('Buat Case Project')
            ->icon('heroicon-o-briefcase')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Buat Case Project dari MoU Ini')
            ->modalDescription('Tindakan ini akan membuat data Case Project baru berdasarkan MoU ini dengan status Case Done. Lanjutkan?')
            ->modalSubmitActionLabel('Ya, Buat')
            ->action(function () {
                $caseProject = CaseProject::create([
                    'mou_id'    => $this->mou->id,
                    'client_id' => $this->mou->client_id,
                    'status'    => 'case_done',
                    'case_date' => now()->toDateString(),
                ]);

                $this->mou->update([
                    'is_make_case_project' => true,
                    'case_project_id'      => $caseProject->id,
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Berhasil')
                    ->body('Case Project berhasil dibuat dengan status Case Done.')
                    ->success()
                    ->send();

                // Kirim notifikasi WhatsApp via Wablas
                try {
                    $mouNumber   = $this->mou->mou_number ?? '-';
                    $clientName  = $this->mou->client?->company_name ?? '-';
                    $caseDate    = now()->locale('id')->translatedFormat('d F Y');

                    $message  = "✅ *CASE PROJECT BERHASIL DIBUAT*\n\n";
                    $message .= "📋 No. MoU\t\t: {$mouNumber}\n";
                    $message .= "🏢 Klien\t\t: {$clientName}\n";
                    $message .= "📆 Tanggal\t\t: {$caseDate}\n";
                    $message .= "🔖 Status\t\t: Case Done\n\n";
                    $message .= "💡 *Catatan:* Mohon dibuatkan *bonus payroll* untuk staff yang menangani case project ini.\n\n";
                    $message .= "Terima kasih,\n";
                    $message .= "_Sistem Rafatax_";

                    /** @var \App\Services\WablasService $wablasService */
                    $wablasService = app(\App\Services\WablasService::class);
                    $wablasService->sendMessage('6285725380708', $message);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim notifikasi Wablas case project: ' . $e->getMessage());
                }
            });
    }

    private function makeEditMouAction(): Actions\EditAction
    {
        return Actions\EditAction::make()
            ->label('Edit MoU')
            ->icon('heroicon-o-pencil')
            ->record($this->mou)
            ->form(fn(Form $form) => MouResource::form($form)->getComponents())
            ->modalWidth('7xl')
            ->color('danger')
            ->successRedirectUrl(fn() => MouResource::getUrl('viewCostList', ['record' => $this->mou]));
    }

    private function makeAddCostListAction(): Actions\CreateAction
    {
        return Actions\CreateAction::make()
            ->label('Add Cost List')
            ->color('info')
            ->icon('heroicon-o-plus')
            ->model(CostListMou::class)
            ->form([
                Select::make('coa_id')
                    ->label('CoA')
                    ->options(Coa::whereIn('id', [119, 120, 121, 122, 123, 124, 125, 126])->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('quantity')
                    ->label('Qty')
                    ->default(1)
                    ->required()
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->live()
                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, ',', '.') : '1')
                    ->dehydrateStateUsing(fn($state) => (float) str_replace('.', '', $state))
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $qty = floatval(str_replace('.', '', $state ?? '0'));
                        $price = floatval(str_replace('.', '', $get('amount') ?? '0'));
                        $set('total_amount', number_format($qty * $price, 0, ',', '.'));
                    }),
                TextInput::make('satuan_quantity')
                    ->label('Satuan'),
                TextInput::make('amount')
                    ->label('Price')
                    ->required()
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->live()
                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, ',', '.') : null)
                    ->dehydrateStateUsing(fn($state) => (float) str_replace('.', '', $state))
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $price = floatval(str_replace('.', '', $state ?? '0'));
                        $qty = floatval(str_replace('.', '', $get('quantity') ?? '1'));
                        $set('total_amount', number_format($qty * $price, 0, ',', '.'));
                    }),
                TextInput::make('total_amount')
                    ->label('Total')
                    ->readOnly()
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, ',', '.') : '0')
                    ->dehydrateStateUsing(fn($state) => (float) str_replace('.', '', $state)),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
            ])
            ->mutateFormDataUsing(function (array $data): array {
                $data['mou_id'] = $this->mou->id;
                return $data;
            })
            ->modalWidth('lg');
    }

    private function makePreviewPdfAction(): Actions\Action
    {
        return Actions\Action::make('preview_pdf')
            ->label('Preview PDF')
            ->icon('heroicon-o-eye')
            ->color('warning')
            ->url(fn() => route('mou.pdf.preview', ['id' => $this->mou->id]))
            ->openUrlInNewTab();
    }

    private function makeExportPdfAction(): Actions\Action
    {
        return Actions\Action::make('export_pdf')
            ->label('Print PDF MoU')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(fn() => route('mou.print.view', ['id' => $this->mou->id]))
            ->openUrlInNewTab();
    }

    private function makeSendMouWhatsappAction(): Action
    {
        return Action::make('send_mou_whatsapp')
            ->label('Kirim MoU ke Client')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->form([
                TextInput::make('phone_number')
                    ->label('WhatsApp Number')
                    ->required()
                    ->default(function () {
                        $client = $this->mou->client;
                        return $client?->phone;
                    })
                    ->helperText('Format: 08123456789 atau 628123456789'),
            ])
            ->modalHeading('Kirim MoU ke Client via WhatsApp')
            ->modalDescription('Pastikan nomor WhatsApp sudah benar sebelum mengirim.')
            ->modalSubmitActionLabel('Ya, Kirim')
            ->action(function (array $data) {
                $this->handleSendMouWhatsapp($data);
            });
    }

    private function handleSendMouWhatsapp(array $data): void
    {
        try {
            $phoneInput = $data['phone_number'];

            if (empty($phoneInput)) {
                \Filament\Notifications\Notification::make()
                    ->title('Error')
                    ->body('Nomor WhatsApp wajib diisi!')
                    ->danger()
                    ->send();
                return;
            }

            // Clean phone number
            $phone = preg_replace('/[^0-9]/', '', $phoneInput);
            if (substr($phone, 0, 1) === '0') {
                $phone = '62' . substr($phone, 1);
            } elseif (substr($phone, 0, 2) !== '62') {
                $phone = '62' . $phone;
            }

            // Build caption message
            $ownerName = $this->mou->client?->owner_name ?? 'Bapak/Ibu';
            $mouNumber = $this->mou->mou_number ?? '-';
            $categoryName = $this->mou->categoryMou?->name ?? '-';
            $companyName = $this->mou->client?->company_name ?? '-';

            $caption = "Yth. Bapak/Ibu {$ownerName}\n";
            $caption .= "Kami dari Tim Admin RAFATAX Consulting bersama ini mengirimkan draft MOU Kerjasama untuk tahun 2026.\n";
            $caption .= "Mohon dapat dipelajari dan ditandatangani sebagi bukti persetujuan.\n";
            $caption .= "No Mou \t\t: {$mouNumber}\n";
            $caption .= "Jenis Pekerjaan \t: {$categoryName} {$companyName}\n";
            $caption .= "Ketentuan:\n";
            $caption .= "- MoU wajib di Tandatangani dan di kirim kembali kepada kami Max 7 Hari setelah pesan ini di kirim.\n\n";
            $caption .= "Terima kasih\n";
            $caption .= "Admin Rafatax Consulting";

            // Generate PDF using same logic as MouPrintViewController
            $mou = MoU::with(['client', 'categoryMou'])->findOrFail($this->mou->id);
            $costLists = CostListMou::where('mou_id', $mou->id)->get();

            $format = $mou->type === 'pt'
                ? $mou->categoryMou->format_mou_pt
                : $mou->categoryMou->format_mou_kkp;

            if (!$format) {
                \Filament\Notifications\Notification::make()
                    ->title('Error')
                    ->body('Format print PDF belum diatur untuk kategori MoU ini.')
                    ->danger()
                    ->send();
                return;
            }

            $view = 'format-mous.preview.' . $format;
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, [
                'mou' => $mou,
                'costLists' => $costLists,
                'printMode' => true,
                'isPdf' => true,
            ])->setPaper('a4', 'portrait')->setOption(['isPhpEnabled' => true, 'compress' => 1]);

            // Save to temporary file
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $mouNumberClean = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $mou->mou_number);
            $filename = 'MoU-' . $mouNumberClean . '.pdf';
            $tempPath = $tempDir . '/' . $filename;

            $pdf->save($tempPath);

            // Send via Wablas
            /** @var \App\Services\WablasService $wablasService */
            $wablasService = app(\App\Services\WablasService::class);

            // Send text caption first
            $wablasService->sendMessage($phone, $caption);

            // Send PDF document
            $sendResult = $wablasService->sendDocument($phone, $tempPath);

            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            if (isset($sendResult['status']) && $sendResult['status']) {
                $this->mou->update([
                    'is_send_mou' => true,
                    'send_mou_date' => now()->toDateString(),
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Berhasil')
                    ->body('MoU berhasil dikirim ke client via WhatsApp.')
                    ->success()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Warning')
                    ->body('Pesan terkirim, tetapi gagal mengirim PDF. ' . ($sendResult['message'] ?? ''))
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Gagal mengirim WhatsApp: ' . $e->getMessage())
                ->danger()
                ->send();
            \Illuminate\Support\Facades\Log::error($e);
        }
    }

    private function makeBackAction(): Action
    {
        return Action::make('back')
            ->label('Back to MoU List')
            ->url(MouResource::getUrl('index'))
            ->color('primary')
            ->icon('heroicon-o-arrow-left');
    }

    private function makeCreateInvoiceAction(): Actions\CreateAction
    {
        return Actions\CreateAction::make('create_invoice')
            ->label('Create Invoice')
            ->color('secondary')
            ->model(Invoice::class)
            ->icon('heroicon-o-document-plus')
            ->form($this->getInvoiceFormSchema())
            ->mutateFormDataUsing(function (array $data): array {
                $data['mou_id'] = $this->mou->id;
                $this->pendingChecklistIds = $data['checklist_mou_ids'] ?? [];
                unset($data['checklist_mou_ids']);
                return $data;
            })
            ->after(function ($record) {
                $this->handlePostInvoiceCreate($record);
            })
            ->modalWidth('7xl');
    }

    private function makeCreateOldInvoiceAction(): Actions\CreateAction
    {
        return Actions\CreateAction::make('create_old_invoice')
            ->label('Tambah Invoice Lama')
            ->color('warning')
            ->model(Invoice::class)
            ->icon('heroicon-o-archive-box')
            ->form($this->getOldInvoiceFormSchema())
            ->mutateFormDataUsing(function (array $data): array {
                $data['mou_id'] = $this->mou->id;
                $this->pendingChecklistIds = $data['checklist_mou_ids'] ?? [];
                unset($data['checklist_mou_ids']);
                return $data;
            })
            ->after(function ($record) {
                $this->handlePostInvoiceCreate($record);
            })
            ->modalWidth('7xl');
    }

    private function handlePostInvoiceCreate($record): void
    {
        if (!empty($this->pendingChecklistIds)) {
            $checklistStatus = $record->invoice_status === 'paid' ? 'completed' : 'pending';
            \App\Models\ChecklistMou::whereIn('id', $this->pendingChecklistIds)->update([
                'invoice_id' => $record->id,
                'status' => $checklistStatus
            ]);
        }
        $this->dispatch('invoice-created');
    }

    private function getInvoiceFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Hidden::make('mou_id')
                ->default($this->mou->id),
            TextInput::make('invoice_number')
                ->label('Invoice Number')
                ->required()
                ->readOnly()
                ->unique(
                    'invoices',
                    'invoice_number',
                    modifyRuleUsing: function ($rule) {
                        return $rule->whereNull('deleted_at');
                    }
                )
                ->suffixAction(
                    \Filament\Forms\Components\Actions\Action::make('regenerate_number')
                        ->icon('heroicon-o-arrow-path')
                        ->tooltip('Regenerate Invoice Number')
                        ->action(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            InvoiceResource::generateInvoiceNumber($set, $get);
                        })
                ),
            DatePicker::make('invoice_date')
                ->label('Tanggal Invoice')
                ->required()
                ->native(false)
                ->displayFormat('d/m/Y')
                ->live()
                ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                    if ($state) {
                        $dueDate = \Carbon\Carbon::parse($state)->addWeeks(2)->toDateString();
                        $set('due_date', $dueDate);
                    }
                    InvoiceResource::generateInvoiceNumber($set, $get);
                })
                ->afterStateHydrated(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get, $state) {
                    InvoiceResource::generateInvoiceNumber($set, $get);
                }),
            DatePicker::make('due_date')
                ->label('Tanggal Jatuh Tempo')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->required(),
            Select::make('invoice_status')
                ->label('Status')
                ->options([
                    'unpaid' => 'Unpaid',
                    'paid' => 'Paid',
                ])
                ->default('unpaid')
                ->required(),
            Select::make('invoice_type')
                ->label('Type')
                ->options([
                    'pt' => 'PT',
                    'kkp' => 'KKP',
                ])
                ->required()
                ->default(fn() => $this->mou->type)
                ->live()
                ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                    InvoiceResource::generateInvoiceNumber($set, $get);
                }),
            Select::make('rek_transfer')
                ->label('Rekening Transfer')
                ->options([
                    'BCA PT' => 'BCA PT',
                    'BCA BARU' => 'BCA BARU',
                    'BCA LAMA' => 'BCA LAMA',
                    'MANDIRI' => 'MANDIRI'
                ]),
            Checkbox::make('is_include_pph23')
                ->label('Checklist Invoice PPH23')
                ->default(false),
            Textarea::make('description')
                ->label('Description')
                ->rows(3),
            ...$this->getCostListInvoiceRepeaterSchema(),
            $this->getChecklistMouField(),
        ];
    }

    private function getOldInvoiceFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Hidden::make('mou_id')
                ->default($this->mou->id),
            TextInput::make('invoice_number')
                ->label('Invoice Number')
                ->required()
                ->unique(
                    'invoices',
                    'invoice_number',
                    modifyRuleUsing: function ($rule) {
                        return $rule->whereNull('deleted_at');
                    }
                ),
            DatePicker::make('invoice_date')
                ->label('Tanggal Invoice')
                ->required()
                ->native(false)
                ->displayFormat('d/m/Y')
                ->default('2025-12-31')
                ->live()
                ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                    if ($state) {
                        $dueDate = \Carbon\Carbon::parse($state)->addWeeks(3)->toDateString();
                        $set('due_date', $dueDate);
                    }
                }),
            DatePicker::make('due_date')
                ->label('Tanggal Jatuh Tempo')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->default('2026-01-21')
                ->required(),
            Select::make('invoice_status')
                ->label('Status')
                ->options([
                    'unpaid' => 'Unpaid',
                    'paid' => 'Paid',
                ])
                ->default('paid')
                ->required(),
            Select::make('invoice_type')
                ->label('Type')
                ->options([
                    'pt' => 'PT',
                    'kkp' => 'KKP',
                ])
                ->required()
                ->default(fn() => $this->mou->type),
            ...$this->getCostListInvoiceRepeaterSchema(),
            $this->getChecklistMouField(),
        ];
    }

    private function getCostListInvoiceRepeaterSchema(): array
    {
        return [
            \Filament\Forms\Components\Section::make('Rincian Biaya')
                ->schema([
                    \Filament\Forms\Components\Repeater::make('costListInvoices')
                        ->relationship()
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('mou_id')
                                ->default($this->mou->id),
                            Select::make('coa_id')
                                ->label('CoA')
                                ->options(Coa::where('group_coa_id', '40')->orWhere('id', '162')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->columnSpan([
                                    'md' => 3,
                                ]),
                            TextInput::make('description')
                                ->label('Deskripsi')
                                ->required()
                                ->columnSpan([
                                    'md' => 4,
                                ]),
                            TextInput::make('amount')
                                ->label('Harga')
                                ->required()
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, ',', '.') : null)
                                ->dehydrateStateUsing(fn($state) => (float) str_replace('.', '', $state))
                                ->columnSpan([
                                    'md' => 5,
                                ]),
                        ])
                        ->columns([
                            'md' => 12,
                        ])
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state['description'] ?? null),
                ]),
        ];
    }

    private function getChecklistMouField(): \Filament\Forms\Components\CheckboxList
    {
        return \Filament\Forms\Components\CheckboxList::make('checklist_mou_ids')
            ->label('Checklist Invoice (Pilih Periode yang akan ditagihkan)')
            ->options(function () {
                return \App\Models\ChecklistMou::where('mou_id', $this->mou->id)
                    ->whereNull('invoice_id')
                    ->orderBy('checklist_date', 'asc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        if ($item->checklist_date === '1000-01-01') {
                            return [$item->id => 'SPT Tahunan'];
                        }

                        $date = \Carbon\Carbon::parse($item->checklist_date)->translatedFormat('F Y');

                        return [$item->id => "Periode: {$date} (" . ($item->notes ?? '-') . ")"];
                    });
            })
            ->visible(fn() => in_array($this->mou->category_mou_id, [3, 4]))
            ->columns(2)
            ->gridDirection('row')
            ->bulkToggleable();
    }
}
