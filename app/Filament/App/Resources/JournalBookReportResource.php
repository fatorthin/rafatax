<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\JournalBookReportResource\Pages;
use App\Models\Coa;
use App\Models\JournalBookReport;
use App\Services\JurnalPendapatanService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalBookReportResource extends Resource
{
    protected static ?string $model = JournalBookReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Histori Buku Jurnal';
    protected static ?string $modelLabel = 'Histori Buku Jurnal';
    protected static ?string $pluralModelLabel = 'Histori Buku Jurnal';
    protected static ?int $navigationSort = 15;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('journal-book-report.view')
            || $user->hasPermission('journal-book-reports.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('journal-book-report.create');
    }

    public static function canEdit(Model $record): bool
    {
        if ($record->journal_book_id == JurnalPendapatanService::getJurnalPendapatanId()) {
            return false;
        }

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('journal-book-report.edit');
    }

    public static function canDelete(Model $record): bool
    {
        if ($record->journal_book_id == JurnalPendapatanService::getJurnalPendapatanId()) {
            return false;
        }

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('journal-book-report.delete');
    }

    public static function canForceDelete(Model $record): bool
    {
        if ($record->journal_book_id == JurnalPendapatanService::getJurnalPendapatanId()) {
            return false;
        }

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('cash-manager')
            || $user->hasPermission('journal-book-report.delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->maxLength(500)
                    ->label('Deskripsi'),
                Forms\Components\Select::make('journal_book_id')
                    ->relationship(
                        'journal_book',
                        'name',
                        fn(Builder $query) => $query->where('id', '!=', JurnalPendapatanService::getJurnalPendapatanId())
                    )
                    ->label('Buku Jurnal')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('kepemilikan')
                    ->label('Kepemilikan')
                    ->options([
                        'PT' => 'PT',
                        'KKP' => 'KKP',
                    ])
                    ->default('KKP')
                    ->required(),
                Forms\Components\Select::make('coa_id')
                    ->label('CoA')
                    ->options(function () {
                        return Coa::all()->mapWithKeys(function ($coa) {
                            return [$coa->id => $coa->code . ' - ' . $coa->name];
                        });
                    })
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('debit_amount')
                    ->numeric()
                    ->required()
                    ->label('Debit')
                    ->default(0),
                Forms\Components\TextInput::make('credit_amount')
                    ->numeric()
                    ->required()
                    ->label('Kredit')
                    ->default(0),
                Forms\Components\DatePicker::make('transaction_date')
                    ->required()
                    ->label('Tanggal Transaksi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('kepemilikan')
                    ->label('Kepemilikan')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PT' => 'primary',
                        'KKP' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('journal_book.name')
                    ->label('Buku Jurnal')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('coa.name')
                    ->label('CoA')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Debit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                    ),
                Tables\Columns\TextColumn::make('credit_amount')
                    ->label('Kredit')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return number_format((float)$state, 0, ',', '.');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Sum of Credit')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kepemilikan')
                    ->label('Kepemilikan')
                    ->options([
                        'PT' => 'PT',
                        'KKP' => 'KKP',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
                Tables\Actions\ForceDeleteAction::make()
                    ->requiresConfirmation(),
                Tables\Actions\RestoreAction::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalBookReports::route('/'),
            'create' => Pages\CreateJournalBookReport::route('/create'),
            'edit' => Pages\EditJournalBookReport::route('/{record}/edit'),
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
