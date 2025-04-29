<x-filament::page>
    <div class="grid grid-cols-1 gap-6">
        {{-- Header Stats Section --}}
        <div class="space-y-6">
            @if ($this->hasHeaderWidgets)
                <x-filament::widgets
                    :widgets="$this->getHeaderWidgets()"
                    :columns="$this->getHeaderWidgetsColumns()"
                    class="bg-white dark:bg-gray-800 shadow-sm rounded-xl"
                />
            @endif
        </div>
    </div>
</x-filament::page> 