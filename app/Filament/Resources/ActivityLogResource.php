<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Resources\ActivityLogResource\RelationManagers;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Activity Logs';
    }

    public static function getPluralLabel(): string
    {
        return 'Activity Logs';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')
                    ->label('User')
                    ->formatStateUsing(fn($state, $record) => $record->user?->name ?? 'System')
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->disabled(),
                Forms\Components\TextInput::make('model_type')
                    ->label('Model')
                    ->disabled(),
                Forms\Components\TextInput::make('model_id')
                    ->label('Model ID')
                    ->disabled(),
                Forms\Components\KeyValue::make('old_data')
                    ->label('Old Data')
                    ->disabled(),
                Forms\Components\KeyValue::make('new_data')
                    ->label('New Data')
                    ->disabled(),
                Forms\Components\TextInput::make('ip_address')
                    ->disabled(),
                Forms\Components\TextInput::make('user_agent')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        'restore' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn($state) => class_basename($state))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model_id')
                    ->label('Model ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'restore' => 'Restore',
                    ]),
                Tables\Filters\SelectFilter::make('model_type')
                    ->options([
                        'App\\Models\\Invoice' => 'Invoice',
                        'App\\Models\\MoU' => 'MoU',
                        'App\\Models\\Client' => 'Client',
                        'App\\Models\\CashReport' => 'Cash Report',
                        'App\\Models\\CashReference' => 'Cash Reference',
                        'App\\Models\\Coa' => 'CoA',
                        'App\\Models\\StaffAttendance' => 'Staff Attendance',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return Gate::allows('viewAny', ActivityLog::class);
    }
}
