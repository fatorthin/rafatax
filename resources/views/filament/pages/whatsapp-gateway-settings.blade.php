<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Card Status Server Gateway --}}
        <div class="p-6 transition bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div class="flex items-center gap-4">
                    @if ($gatewayStatus['connected'] ?? false)
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400">
                            <x-heroicon-o-check-circle class="w-7 h-7" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Status Gateway: Online</h3>
                                <span class="px-2.5 py-0.5 text-xs font-semibold text-emerald-700 bg-emerald-100 rounded-full dark:bg-emerald-900 dark:text-emerald-300">Terhubung</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Server URL: <code class="px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">{{ config('services.whatsapp_gateway.url') }}</code>
                                @if (config('services.whatsapp_gateway.device_id'))
                                    | Device ID: <code class="px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">{{ config('services.whatsapp_gateway.device_id') }}</code>
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400">
                            <x-heroicon-o-x-circle class="w-7 h-7" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Status Gateway: Offline / Belum Terhubung</h3>
                                <span class="px-2.5 py-0.5 text-xs font-semibold text-rose-700 bg-rose-100 rounded-full dark:bg-rose-900 dark:text-rose-300">Tidak Terhubung</span>
                            </div>
                            <p class="text-sm text-rose-600 dark:text-rose-400 mt-1">
                                {{ $gatewayStatus['message'] ?? 'Tidak dapat terhubung ke server WhatsApp Gateway.' }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button wire:click="checkGatewayStatus" color="gray" icon="heroicon-o-arrow-path" size="sm">
                        Cek Status
                    </x-filament::button>

                    {{ $this->testSendAction }}

                    @if ($gatewayStatus['connected'] ?? false)
                        {{ $this->logoutDeviceAction }}
                    @endif
                </div>
            </div>

            @if (!empty($gatewayStatus['data']))
                <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Detail Perangkat / Profil:</h4>
                    <div class="p-3 font-mono text-xs text-gray-800 bg-gray-50 dark:bg-gray-900 dark:text-gray-200 rounded-lg overflow-x-auto">
                        <pre>{{ json_encode($gatewayStatus['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            @endif
        </div>

        {{-- Form Setting --}}
        <form wire:submit.prevent="saveSettings" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end gap-3">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                    Simpan Pengaturan
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
