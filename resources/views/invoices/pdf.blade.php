<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Invoice {{ $invoice->invoice_number ?? '' }}</h2>
        <div>{{ optional($invoice->mou)->mou_number ?? '' }}</div>
        <div>{{ optional(optional($invoice->mou)->client)->company_name ?? '' }}</div>
    </div>

    <div class="section">
        <strong>Invoice Date:</strong> {{ $invoice->invoice_date }}<br>
        <strong>Due Date:</strong> {{ $invoice->due_date }}
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($costLists as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="text-right"><strong>Rp
                            {{ number_format($costLists->sum('amount'), 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    @if (isset($error))
        <div style="color: red;">PDF generation error: {{ $error }}</div>
    @endif
</body>

</html>
