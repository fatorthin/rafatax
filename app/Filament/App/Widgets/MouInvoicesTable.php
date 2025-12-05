<?php

namespace App\Filament\App\Widgets;

use App\Filament\Widgets\MouInvoicesTable as BaseMouInvoicesTable;

class MouInvoicesTable extends BaseMouInvoicesTable
{
    // This class exists to expose the same widget under the App panel namespace
    // so Filament's `app` panel can discover and render it as
    // `app.filament.widgets.mou-invoices-table`.
}
