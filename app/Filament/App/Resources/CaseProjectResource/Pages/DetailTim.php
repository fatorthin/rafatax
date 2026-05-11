<?php

namespace App\Filament\App\Resources\CaseProjectResource\Pages;

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
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\DeleteAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\App\Resources\CaseProjectResource;
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
                ->modalSubmitActionLabel('Simpan')
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
                ]),

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
            ->headerActions([
                \Filament\Tables\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\CaseProjectDetailExporter::class)
                    ->csvDelimiter(';')
                    ->label('Export CSV'),
                \Filament\Tables\Actions\Action::make('import_csv')
                    ->label('Import CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('Upload File CSV')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'text/comma-separated-values'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($data['file']);
                        if (!file_exists($filePath)) {
                            $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);
                        }

                        $content = file_get_contents($filePath);
                        $delimiter = strpos($content, ';') !== false ? ';' : ',';

                        $csv = \League\Csv\Reader::createFromString($content);
                        $csv->setDelimiter($delimiter);
                        $csv->setHeaderOffset(0);

                        $records = $csv->getRecords();
                        $count = 0;
                        foreach ($records as $record) {
                            $recordKeys = array_keys($record);

                            // Deteksi jika file CSV rusak karena Excel menyatukan kolom dalam tanda kutip
                            if (count($recordKeys) === 1 && strpos($recordKeys[0], ',') !== false) {
                                $realHeaders = str_getcsv($recordKeys[0], ',', '"');
                                $realData = str_getcsv(array_values($record)[0] ?? '', ',', '"');
                                if (count($realHeaders) === count($realData)) {
                                    $record = array_combine($realHeaders, $realData);
                                    $recordKeys = array_keys($record);
                                }
                            }

                            // Cari key tanpa memperdulikan case atau spasi tambahan
                            $idKey = collect($recordKeys)->first(fn($k) => strtolower(trim($k, '"\' ')) === 'id');
                            $bonusKey = collect($recordKeys)->first(fn($k) => strtolower(trim($k, '"\' ')) === 'bonus');

                            if ($idKey && $bonusKey) {
                                $id = preg_replace('/[^0-9]/', '', $record[$idKey]);
                                $bonusRaw = $record[$bonusKey];
                                $bonus = preg_replace('/[^0-9]/', '', $bonusRaw);

                                if ($id) {
                                    $model = CaseProjectDetail::find($id);
                                    if ($model) {
                                        $model->bonus = $bonus ?: 0;
                                        $model->save();
                                        $count++;
                                    }
                                }
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Import Berhasil')
                            ->body("$count baris data berhasil diupdate.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Detail Tim')
                    ->modalSubmitActionLabel('Simpan')
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
                    ->modalSubmitActionLabel('Hapus'),
            ]);
    }
}
