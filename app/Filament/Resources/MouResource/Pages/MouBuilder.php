<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Models\MoU;
use App\Models\MouTemplate;
use Filament\Resources\Pages\Page;
use App\Filament\Resources\MouResource;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class MouBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = MouResource::class;

    protected static string $view = 'filament.resources.mou-resource.pages.mou-builder';

    public MoU $mou;

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->mou = MoU::with(['client', 'categoryMou', 'mouTemplate', 'cost_lists'])->findOrFail($record);

        // Inisialisasi form state
        $hasCustom = (bool) $this->mou->has_custom_builder;
        $sections = $this->mou->custom_sections;

        // Jika belum ada custom sections, berikan default template
        if (empty($sections)) {
            $sections = MouTemplate::getDefaultSections();
        }

        $this->form->fill([
            'mou_template_id' => $this->mou->mou_template_id,
            'has_custom_builder' => $hasCustom,
            'custom_sections' => $sections,
        ]);
    }

    public function getTitle(): string
    {
        return 'MoU Builder - ' . ($this->mou->mou_number ?: 'MoU #' . $this->mou->id);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Konfigurasi MoU Builder')
                    ->description('Aktifkan custom builder jika ingin menyusun pasal atau klausul secara khusus untuk dokumen MoU ini. Jika dinonaktifkan, MoU akan kembali dicetak menggunakan format template standar sistem.')
                    ->schema([
                        Toggle::make('has_custom_builder')
                            ->label('Gunakan Custom MoU Builder')
                            ->helperText('Aktifkan agar hasil cetak/PDF dan WhatsApp menggunakan susunan pasal di bawah ini')
                            ->default(true)
                            ->live(),
                        Select::make('mou_template_id')
                            ->label('Pilih Master Template (Opsional)')
                            ->placeholder('-- Pilih Template untuk di-Load --')
                            ->options(MouTemplate::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $template = MouTemplate::find($state);
                                    if ($template && !empty($template->sections)) {
                                        $set('custom_sections', $template->sections);
                                        Notification::make()
                                            ->title('Template Dimuat')
                                            ->body("Pasal-pasal berhasil disalin dari master template \"{$template->name}\".")
                                            ->success()
                                            ->send();
                                    }
                                }
                            }),
                    ])->columns(2),

                Section::make('Daftar Pasal & Komponen MoU')
                    ->description('Susun pasal-pasal perjanjian secara bebas. Anda dapat menambah, menghapus, mengubah urutan (drag/geser), mengedit isi tiap pasal, serta menentukan pasal mana yang menyertakan tabel rincian biaya (Cost List).')
                    ->headerActions([
                        \Filament\Forms\Components\Actions\Action::make('reset_to_default')
                            ->label('Reset ke Pasal Standar')
                            ->icon('heroicon-m-arrow-path')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalHeading('Reset Semua Pasal?')
                            ->modalDescription('Apakah Anda yakin ingin mengatur ulang daftar pasal ke draf standar sistem?')
                            ->action(function (callable $set) {
                                $set('custom_sections', MouTemplate::getDefaultSections());
                                Notification::make()
                                    ->title('Daftar pasal di-reset ke standar')
                                    ->warning()
                                    ->send();
                            }),
                    ])
                    ->schema([
                        Repeater::make('custom_sections')
                            ->label('Pasal-Pasal MoU')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Judul Pasal')
                                    ->required()
                                    ->placeholder('Misal: Tujuan dan Ruang Lingkup')
                                    ->columnSpan(8),
                                Toggle::make('include_cost_table')
                                    ->label('Sertakan Tabel Biaya?')
                                    ->helperText('Otomatis tampilkan tabel biaya di pasal ini')
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
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview_pdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->url(fn () => route('mou.pdf.preview', ['id' => $this->mou->id]))
                ->openUrlInNewTab(),
            Action::make('print_pdf')
                ->label('Print View')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('mou.print.view', ['id' => $this->mou->id]))
                ->openUrlInNewTab(),
            Action::make('back_to_mou')
                ->label('Kembali ke Detail MoU')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => MouResource::getUrl('viewCostList', ['record' => $this->mou])),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $this->mou->update([
            'has_custom_builder' => (bool) ($state['has_custom_builder'] ?? false),
            'mou_template_id' => $state['mou_template_id'] ?? null,
            'custom_sections' => $state['custom_sections'] ?? [],
        ]);

        Notification::make()
            ->title('Berhasil Disimpan')
            ->body('Konfigurasi MoU Builder untuk dokumen ini berhasil disimpan.')
            ->success()
            ->send();
    }
}
