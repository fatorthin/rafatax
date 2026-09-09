<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MouTemplateResource\Pages;
use App\Models\MouTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class MouTemplateResource extends Resource
{
    protected static ?string $model = MouTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Master Template MoU';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Template')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Template')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Template Supervisi Bulanan PT'),
                        Select::make('type')
                            ->label('Tipe Format')
                            ->options([
                                'both' => 'Keduanya (PT & KKP)',
                                'pt' => 'Hanya PT',
                                'kkp' => 'Hanya KKP',
                            ])
                            ->default('both')
                            ->required(),
                        Textarea::make('description')
                            ->label('Deskripsi / Catatan Template')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Susunan Pasal / Section MoU')
                    ->description('Atur judul pasal, konten penjelasan/pasal, serta opsi apakah pasal tersebut menyertakan tabel rincian biaya (Cost List). Anda dapat menggunakan placeholder dinamis seperti {NAMA_KLIEN}, {NAMA_PEMILIK}, {PERUSAHAAN}, {ALAMAT}, {TAHUN_PAJAK}, {TANGGAL_AWAL}, {TANGGAL_AKHIR}, {TOTAL_NOMINAL}.')
                    ->headerActions([
                        Forms\Components\Actions\Action::make('load_default_preset')
                            ->label('Muat Pasal Standar')
                            ->icon('heroicon-m-arrow-path')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Muat Format Pasal Standar?')
                            ->modalDescription('Tindakan ini akan mengisi daftar pasal dengan draf standar bawaan sistem.')
                            ->action(function (Forms\Set $set) {
                                $set('sections', MouTemplate::getDefaultSections());
                            })
                    ])
                    ->schema([
                        Repeater::make('sections')
                            ->label('Daftar Pasal / Komponen')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Judul Pasal')
                                    ->required()
                                    ->placeholder('Misal: Tujuan dan Ruang Lingkup')
                                    ->columnSpan(8),
                                Toggle::make('include_cost_table')
                                    ->label('Sertakan Tabel Biaya?')
                                    ->helperText('Tampilkan tabel rincian tagihan di bawah pasal ini')
                                    ->columnSpan(4),
                                RichEditor::make('content')
                                    ->label('Isi Pasal')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'bulletList',
                                        'orderedList',
                                        'redo',
                                        'undo',
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Pasal Baru')
                            ->default(MouTemplate::getDefaultSections())
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                        default => 'PT & KKP',
                    })
                    ->color(fn ($state) => match ($state) {
                        'pt' => 'info',
                        'kkp' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('sections_count')
                    ->label('Jumlah Pasal')
                    ->getStateUsing(fn (MouTemplate $record) => is_array($record->sections) ? count($record->sections) : 0)
                    ->alignCenter(),
                TextColumn::make('mous_count')
                    ->label('Digunakan di MoU')
                    ->counts('mous')
                    ->alignCenter(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMouTemplates::route('/'),
            'create' => Pages\CreateMouTemplate::route('/create'),
            'edit' => Pages\EditMouTemplate::route('/{record}/edit'),
        ];
    }
}
