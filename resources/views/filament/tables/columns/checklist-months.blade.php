@php
    $record = $getRecord();
    $start = \Carbon\Carbon::parse($record->start_date)->startOfMonth();
    $end = \Carbon\Carbon::parse($record->end_date)->startOfMonth();

    // Get all checklists for this MoU (should be eager loaded or lazy loaded once)
    // Using collection filtering instead of query to avoid N+1 if eager loaded,
    // or just efficient enough for now.
    $checklists = $record->checklistMous->keyBy(
        fn($item) => \Carbon\Carbon::parse($item->checklist_date)->format('Y-m'),
    );

    $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);
@endphp

<div class="flex gap-2 w-full overflow-x-auto pb-2">
    @foreach ($period as $date)
        @php
            $key = $date->format('Y-m');
            $checklist = $checklists->get($key);
            $status = $checklist ? $checklist->status : 'none';

            $monthName = $date->translatedFormat('M'); // Jan, Feb, etc.
            $yearStr = $date->format('Y');

            $colors = [
                'none' => [
                    'style' => 'background-color: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;',
                ],
                'pending' => [
                    'style' => 'background-color: #fef9c3; color: #854d0e; border: 1px solid #fef08a;',
                ],
                'completed' => [
                    'style' => 'background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0;',
                ],
                'overdue' => [
                    'style' => 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;',
                ],
            ];

            $style = $colors[$status]['style'] ?? $colors['none']['style'];
            $label = $monthName . ' ' . $yearStr;
        @endphp

        <button type="button"
            wire:click="mountAction('editChecklist', { mou_id: {{ $record->id }}, date: '{{ $date->format('Y-m-d') }}' })"
            class="px-2 py-1 text-xs rounded text-center min-w-[80px] shrink-0 cursor-pointer hover:opacity-80 transition-opacity"
            style="{{ $style }}">
            {{ $label }}
            @if ($status !== 'none')
                <div class="text-[10px] font-semibold mt-0.5" style="text-transform: uppercase;">{{ $status }}</div>
            @else
                <div class="text-[10px] font-semibold mt-0.5" style="text-transform: uppercase;">-</div>
            @endif
        </button>
    @endforeach
</div>
