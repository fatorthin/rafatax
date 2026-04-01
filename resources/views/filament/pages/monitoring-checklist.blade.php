<x-filament-panels::page>
    <style>
        /* Limit table height to allow vertical scrolling within the table */
        .fi-ta-content {
            max-height: 75vh;
            overflow: auto !important;
        }

        /* Make the table header sticky at the top */
        .fi-ta-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        /* Ensure header background overlaps scrollable body content */
        .fi-ta-table thead th {
            background-color: #f9fafb; /* Filament bg-gray-50 */
        }

        .dark .fi-ta-table thead th {
            background-color: #18181b; /* Filament dark bg-zinc-900 */
        }
    </style>

    {{ $this->table }}
</x-filament-panels::page>
