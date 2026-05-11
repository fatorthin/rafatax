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
                                'case_done' => 'Case Done',
                                'bonus_done' => 'Bonus Done',
                                'paid' => 'Paid',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'open' => 'primary',
                                'in_progress' => 'warning',
                                'done' => 'success',
                                'case_done' => 'gray',
                                'bonus_done' => 'info',
                                'paid' => 'success',
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
                \Filament\Tables\Actions\Action::make('export_xlsx')
                    ->label('Export XLSX')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $details = CaseProjectDetail::with('staff')
                            ->where('case_project_id', $this->record->id)
                            ->get();

                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();

                        // Header
                        $sheet->fromArray(['ID', 'Nama Staff', 'Bonus'], null, 'A1');
                        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

                        // Data
                        $row = 2;
                        foreach ($details as $detail) {
                            $sheet->fromArray([
                                $detail->id,
                                $detail->staff?->name ?? '-',
                                $detail->bonus,
                            ], null, "A{$row}");
                            $row++;
                        }

                        // Auto-size kolom
                        foreach (range('A', 'C') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }

                        $fileName = 'detail_tim_' . $this->record->id . '_' . now()->format('Ymd_His') . '.xlsx';
                        $tempPath = storage_path('app/public/' . $fileName);

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save($tempPath);

                        return response()->download($tempPath, $fileName, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    }),

                \Filament\Tables\Actions\Action::make('import_xlsx')
                    ->label('Import XLSX')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('Upload File XLSX')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($data['file']);
                        if (!file_exists($filePath)) {
                            $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);
                        }

                        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                        $sheet = $spreadsheet->getActiveSheet();
                        $rows = $sheet->toArray();

                        if (empty($rows)) {
                            \Filament\Notifications\Notification::make()
                                ->title('File Kosong')
                                ->body('File XLSX tidak memiliki data.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Baris pertama = header
                        $headers = array_map(fn($h) => strtolower(trim((string) $h)), $rows[0]);
                        $idIndex = array_search('id', $headers);
                        $bonusIndex = array_search('bonus', $headers);

                        if ($idIndex === false || $bonusIndex === false) {
                            \Filament\Notifications\Notification::make()
                                ->title('Format Tidak Valid')
                                ->body('Kolom "id" dan "bonus" harus ada di baris pertama.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $count = 0;
                        foreach (array_slice($rows, 1) as $row) {
                            $id = isset($row[$idIndex]) ? preg_replace('/[^0-9]/', '', (string) $row[$idIndex]) : null;
                            $bonus = isset($row[$bonusIndex]) ? preg_replace('/[^0-9]/', '', (string) $row[$bonusIndex]) : 0;

                            if ($id) {
                                $model = CaseProjectDetail::find($id);
                                if ($model) {
                                    $model->bonus = $bonus ?: 0;
                                    $model->save();
                                    $count++;
                                }
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Import Berhasil')
                            ->body("{$count} baris data berhasil diupdate.")
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
