<?php

namespace App\Filament\Resources\CaseProjectResource\Pages;

use App\Models\Staff;
use Filament\Tables\Table;
use App\Models\CaseProject;
use Filament\Infolists\Infolist;
use App\Models\CaseProjectDetail;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\DeleteAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\CaseProjectResource;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\Summarizers\Summarizer;

class DetailTim extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = CaseProjectResource::class;

    protected static string $view = 'filament.resources.case-project-resource.pages.detail-tim';

    public CaseProject $record;

    public function getTitle(): string
    {
        return 'Detail Tim Kasus Proyek - ' . $this->record->description;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Detail')
                ->modalHeading('Tambah Detail Tim')
                ->modalButton('Simpan')
                ->model(CaseProjectDetail::class)
                ->form([
                    Hidden::make('case_project_id')
                        ->default(fn() => $this->record->id),

                    Select::make('staff_id')
                        ->label('Staff')
                        ->relationship('staff', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('bonus')
                        ->label('Bonus (Rp)')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    CaseProjectDetail::create($data);

                    \Filament\Notifications\Notification::make()
                        ->title('Berhasil')
                        ->body('Detail tim berhasil ditambahkan')
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
                Section::make('Informasi Kasus Proyek')
                    ->schema([
                        TextEntry::make('description')->label('Deskripsi'),
                        TextEntry::make('client.company_name')->label('Client'),
                        TextEntry::make('case_type')->label('Tipe Kasus'),
                        TextEntry::make('staff_members')
                            ->label('Staff')
                            ->badge()
                            ->state(fn(CaseProject $record) => Staff::whereIn('id', $record->staff_id ?? [])->pluck('name')->toArray()),
                        TextEntry::make('case_date')->label('Tanggal Proyek')->date('d-m-Y'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'open' => 'Open',
                                'in_progress' => 'In Progress',
                                'done' => 'Done',
                                default => $state,
                            })
                            ->color(fn($state) => match ($state) {
                                'open' => 'primary',
                                'in_progress' => 'danger',
                                'done' => 'success',
                                default => 'gray',
                            }),
                    ])->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CaseProjectDetail::query()->where('case_project_id', $this->record->id))
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->state(fn($record, $rowLoop) => $rowLoop->iteration)
                    ->sortable(),

                TextColumn::make('staff.name')
                    ->label('Nama Staff')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('bonus')
                    ->label('Bonus (Rp)')
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Bonus:')
                            ->using(function ($query) {
                                $total = $query->sum('bonus');
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            })
                    ),
            ])
            ->paginated(false)
            ->striped()
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Detail Tim')
                    ->modalButton('Simpan')
                    ->form([
                        Select::make('staff_id')
                            ->label('Staff')
                            ->relationship('staff', 'name')
                            ->required(),

                        TextInput::make('bonus')
                            ->label('Bonus (Rp)')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (CaseProjectDetail $record, array $data): void {
                        // Hitung total bonus yang sudah ada (kecuali record yang sedang diedit)
                        $existingTotalBonus = CaseProjectDetail::where('case_project_id', $record->case_project_id)
                            ->where('id', '!=', $record->id)
                            ->sum('bonus');

                        // Hitung total bonus jika data diedit
                        $newTotalBonus = $existingTotalBonus + $data['bonus'];

                        // Cek apakah melebihi budget
                        $budget = $this->record->budget;

                        if ($newTotalBonus > $budget) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Mengubah Data')
                                ->body('Total bonus (Rp ' . number_format($newTotalBonus, 0, ',', '.') . ') akan melebihi budget proyek (Rp ' . number_format($budget, 0, ',', '.') . ')')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update($data);

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil')
                            ->body('Detail tim berhasil diubah')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->modalHeading('Hapus Detail Tim')
                    ->requiresConfirmation()
                    ->modalButton('Hapus'),
            ]);
    }
}
