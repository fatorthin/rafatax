<?php

namespace App\Filament\Pages;

use App\Models\CategoryMou;
use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;

class RekapInvoiceKasus extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static string $view = 'filament.pages.rekap-invoice-kasus';

    protected static ?string $slug = 'rekap-invoice-kasus';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasPermission('invoice.view') || $user->hasPermission('invoices.view');
    }

    public function getTitle(): string
    {
        return 'Rekap Invoice Berdasarkan Kasus';
    }

    public function table(Table $table): Table
    {
        // Get all unique years from invoices
        $years = Invoice::query()
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $selects = [
            'category_mous.id',
            'category_mous.name as category_name',
        ];

        foreach ($years as $year) {
            $selects[] = DB::raw("(
                SELECT COALESCE(SUM(cli.amount), 0)
                FROM cost_list_invoices as cli
                JOIN invoices as i ON cli.invoice_id = i.id
                LEFT JOIN mous as m ON COALESCE(i.mou_id, cli.mou_id) = m.id
                WHERE m.category_mou_id = category_mous.id
                AND YEAR(i.created_at) = {$year}
                AND i.deleted_at IS NULL
                AND cli.deleted_at IS NULL
                AND m.deleted_at IS NULL
            ) as total_{$year}");
        }

        $columns = [
            TextColumn::make('category_name')
                ->label('Kategori Kasus / MoU')
                ->sortable()
                ->searchable(),
        ];

        foreach ($years as $year) {
            $columns[] = TextColumn::make("total_{$year}")
                ->label("Nominal {$year}")
                ->formatStateUsing(fn($state): string => 'Rp ' . number_format((float) ($state ?? 0), 0, ',', '.'))
                ->sortable()
                ->alignEnd();
        }

        return $table
            ->query(
                CategoryMou::query()
                    ->select($selects)
            )
            ->columns($columns)
            ->paginated(false);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->id;
    }
}
