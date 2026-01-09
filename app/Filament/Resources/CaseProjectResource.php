<?php

namespace App\Filament\Resources;

use App\Models\MoU;
use Filament\Forms;
use Filament\Tables;
use App\Models\Staff;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CaseProject;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CaseProjectResource\Pages;

class CaseProjectResource extends Resource
{
    protected static ?string $model = CaseProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Daftar Proyek Kasus';

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
                    ->label('Tanggal Surat Kasus'),
                Forms\Components\TextInput::make('power_of_attorney_number')
                    ->label('No Surat Kuasa'),
                Forms\Components\DatePicker::make('power_of_attorney_date')
                    ->label('Tanggal Surat Kuasa'),
                Forms\Components\DatePicker::make('filling_drive')
                    ->label('Tangggal Filling Drive'),
                Forms\Components\DatePicker::make('report_date')
                    ->label('Tanggal Laporan'),
                Forms\Components\DatePicker::make('share_client_date')
                    ->label('Tanggal Berikan Client'),
                // Forms\Components\DatePicker::make('case_date')
                //     ->required()
                //     ->label('Tanggal Kasus'),
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
                Tables\Columns\TextColumn::make('description')->sortable()->searchable(),
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
                Tables\Columns\TextColumn::make('status')->label('Status')->sortable()->searchable()->badge()->formatStateUsing(function ($state) {
                    return match ($state) {
                        'open' => 'OPEN',
                        'in_progress' => 'IN PROGRESS',
                        'done' => 'DONE',
                    };
                })->color(function ($state) {
                    return match ($state) {
                        'open' => 'primary',
                        'in_progress' => 'warning',
                        'done' => 'success',
                    };
                }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail Tim')
                    ->url(fn($record) => static::getUrl('detail', ['record' => $record]))
                    ->icon('heroicon-o-information-circle'),
                Tables\Actions\EditAction::make(),
            ], position: ActionsPosition::BeforeColumns);
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
