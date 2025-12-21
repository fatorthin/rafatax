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
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\CaseProjectResource\Pages;
use App\Filament\App\Resources\CaseProjectResource\RelationManagers;

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
                Forms\Components\TextInput::make('description'),
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'company_name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->company_name}")
                    ->searchable(['company_name', 'code'])
                    ->label('Nama Client')
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('case_type')
                    ->options([
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
                        return Staff::when($record, fn($query) => $query->orWhereIn('id', $record->staff_id ?? []))
                            ->pluck('name', 'id');
                    })
                    ->multiple()
                    ->label('Nama Staff')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('mou_id')
                    ->label('No MoU')
                    ->options(MoU::all()->mapWithKeys(fn($record) => [
                        $record->id => "{$record->mou_number} - {$record->description}"
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
                Forms\Components\DatePicker::make('report_date')
                    ->label('Tanggal Laporan')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('share_client_date')
                    ->label('Tanggal Berikan Client')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('case_date')
                    ->required()
                    ->label('Tanggal Kasus')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
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
            ->columns([
                Tables\Columns\TextColumn::make('index')->label('No')->rowIndex(),


                Tables\Columns\TextColumn::make('description')->sortable()->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('status')->badge()->formatStateUsing(function ($state) {
                    return match ($state) {
                        'open' => 'OPEN',
                        'in_progress' => 'IN PROGRESS',
                        'done' => 'DONE',
                        default => $state,
                    };
                })->color(function ($state) {
                    return match ($state) {
                        'open' => 'primary',
                        'in_progress' => 'warning',
                        'done' => 'success',
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
                Tables\Columns\TextColumn::make('report_date')->label('Tanggal Laporan')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('share_client_date')->label('Tanggal Berikan Client')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('case_date')->label('Tanggal Kasus')->date('d-m-Y')->sortable(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
