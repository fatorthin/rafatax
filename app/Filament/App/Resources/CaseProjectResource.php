<?php

namespace App\Filament\App\Resources;

use App\Models\MoU;
use Filament\Forms;
use Filament\Tables;
use App\Models\Staff;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CaseProject;
use App\Traits\HasPermissions;
use App\Services\WablasService;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\CaseProjectResource\Pages;

class CaseProjectResource extends Resource
{
    use HasPermissions;

    protected static ?string $model = CaseProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Daftar Projek Kasus';

    protected static ?string $navigationGroup = 'HRD';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required(),
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'company_name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->company_name}")
                    ->searchable(['company_name', 'code'])
                    ->label('Nama Client')
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('case_type')
                    ->options([
                        'SPT' => 'SPT',
                        'SP2DK' => 'SP2DK',
                        'Pembetulan' => 'PEMBETULAN',
                        'Pemeriksaan' => 'PEMERIKSAAN',
                        'Himbauan' => 'HIMBAUAN',
                        'Lainnya' => 'LAINNYA',
                    ])
                    ->label('Kategori')
                    ->required(),
                Forms\Components\Select::make('staff_id')
                    ->options(function (?CaseProject $record) {
                        return Staff::query()
                            ->where('is_active', true)
                            ->when($record, fn($query) => $query->orWhereIn('id', $record->staff_id ?? []))
                            ->pluck('name', 'id');
                    })
                    ->multiple()
                    ->label('Nama Staff')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('mou_id')
                    ->label('No MoU')
                    ->options(MoU::with('client')->get()->mapWithKeys(fn($record) => [
                        $record->id => "{$record->mou_number} - {$record->client?->company_name} - {$record->description}"
                    ]))
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('case_letter_number')
                    ->label('No Surat Kasus'),
                Forms\Components\DatePicker::make('case_letter_date')
                    ->label('Tanggal Surat Kasus')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\TextInput::make('power_of_attorney_number')
                    ->label('No Surat Kuasa'),
                Forms\Components\DatePicker::make('power_of_attorney_date')
                    ->label('Tanggal Surat Kuasa')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('filling_drive')
                    ->label('Drive Pengisian')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\Textarea::make('link_drive')
                    ->label('Link Drive'),
                Forms\Components\DatePicker::make('report_date')
                    ->label('Tanggal Laporan')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('share_client_date')
                    ->label('Tanggal Berikan Client')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                // Forms\Components\DatePicker::make('case_date')
                //     ->required()
                //     ->label('Tanggal Kasus')
                //     ->native(false)
                //     ->displayFormat('d/m/Y'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'OPEN',
                        'in_progress' => 'IN PROGRESS',
                        'done' => 'DONE',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('index')->label('No')->rowIndex(),


                Tables\Columns\TextColumn::make('description')->sortable()->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('status')->badge()->formatStateUsing(function ($state) {
                    return match ($state) {
                        'open' => 'OPEN',
                        'in_progress' => 'IN PROGRESS',
                        'done' => 'DONE',
                        'paid' => 'PAID',
                        default => $state,
                    };
                })->color(function ($state) {
                    return match ($state) {
                        'open' => 'primary',
                        'in_progress' => 'warning',
                        'done' => 'success',
                        'paid' => 'info',
                        default => 'gray',
                    };
                }),


                Tables\Columns\TextColumn::make('client.code')->label('Kode Client')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Nama Client')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('case_type')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('staff_id')
                    ->label('Nama Pelaksana')
                    ->formatStateUsing(function ($state, CaseProject $record) {
                        $staffIds = $record->staff_id ?? [];
                        return Staff::whereIn('id', $staffIds)->pluck('name')->join(', ');
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('mou.mou_number')->label('No MoU')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('case_letter_number')->label('No Surat Kasus')->sortable(),
                Tables\Columns\TextColumn::make('case_letter_date')->label('Tanggal Surat Kasus')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('power_of_attorney_number')->label('No Surat Kuasa')->sortable(),
                Tables\Columns\TextColumn::make('power_of_attorney_date')->label('Tanggal Surat Kuasa')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('filling_drive')->label('Drive Pengisian')->sortable(),
                Tables\Columns\TextColumn::make('link_drive')
                    ->label('Link Drive')
                    ->formatStateUsing(fn($state) => $state ? 'Buka Link' : '-')
                    ->url(fn($record) => $record->link_drive, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->icon('heroicon-o-link')
                    ->iconPosition('before'),
                Tables\Columns\TextColumn::make('report_date')->label('Tanggal Laporan')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('share_client_date')->label('Tanggal Berikan Client')->date('d-m-Y')->sortable(),
                // Tables\Columns\TextColumn::make('case_date')->label('Tanggal Kasus')->date('d-m-Y')->sortable(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'OPEN',
                        'in_progress' => 'IN PROGRESS',
                        'done' => 'DONE',
                        'paid' => 'PAID',
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('case_type')
                    ->options([
                        'SPT' => 'SPT',
                        'SP2DK' => 'SP2DK',
                        'Pembetulan' => 'PEMBETULAN',
                        'Pemeriksaan' => 'PEMERIKSAAN',
                        'Himbauan' => 'HIMBAUAN',
                        'Lainnya' => 'LAINNYA',
                    ])
                    ->label('Jenis Kasus'),
            ])
            ->actions([
                Tables\Actions\Action::make('sendNotification')
                    ->label('Kirim Notifikasi')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Notifikasi Invoice & Payroll')
                    ->modalDescription('Apakah Anda yakin ingin mengirim notifikasi ini via WhatsApp?')
                    ->action(function (CaseProject $record, WablasService $wablasService) {
                        $phone = '6281359976015';

                        $staffNames = Staff::whereIn('id', $record->staff_id ?? [])->pluck('name')->join(', ');
                        $clientName = $record->client->company_name ?? '-';
                        $caseDate = $record->case_date ? \Carbon\Carbon::parse($record->case_date)->format('d-m-Y') : '-';

                        $message = "🔔 *NOTIFIKASI INVOICE & PAYROLL*\n\n";
                        $message .= "Case Project berikut memerlukan pembuatan invoice dan sudah dapat diproses payroll bonusnya:\n\n";
                        $message .= "📝 *Deskripsi*: {$record->description}\n";
                        $message .= "🏢 *Client*: {$clientName}\n";
                        $message .= "📁 *Kategori*: {$record->case_type}\n";
                        $message .= "👨‍💻 *Staff*: {$staffNames}\n";
                        $message .= "📅 *Tanggal*: {$caseDate}\n\n";
                        $message .= "Mohon segera diproses. Terima kasih.";

                        $result = $wablasService->sendMessage($phone, $message);

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil')
                                ->body('Notifikasi berhasil dikirim via WhatsApp')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal')
                                ->body('Gagal mengirim notifikasi: ' . ($result['message'] ?? 'Unknown error'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->hidden(function () {
                        /** @var \App\Models\User $user */
                        $user = auth()->user();
                        return $user?->hasRole('inventory-admin') ?? false;
                    }),
                Tables\Actions\Action::make('detail')
                    ->label('Detail Tim')
                    ->url(fn($record) => static::getUrl('detail', ['record' => $record]))
                    ->icon('heroicon-o-information-circle')
                    ->hidden(function () {
                        /** @var \App\Models\User $user */
                        $user = auth()->user();
                        return $user?->hasRole('inventory-admin') ?? false;
                    }),
                Tables\Actions\Action::make('editLinkDrive')
                    ->label('Edit Link Drive')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('link_drive')
                            ->label('Link Drive')
                            ->default(fn(CaseProject $record) => $record->link_drive),
                    ])
                    ->action(function (CaseProject $record, array $data) {
                        $record->update(['link_drive' => $data['link_drive']]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCaseProjects::route('/'),
            'detail' => Pages\DetailTim::route('/{record}/detail'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
