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
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Perangkat: <strong class="text-gray-700 dark:text-gray-200">{{ config('services.whatsapp_gateway.device_id', 'rafatax') }}</strong>
                                @if (!empty($gatewayStatus['jid']))
                                    | No WA: <code class="px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">{{ $gatewayStatus['jid'] }}</code>
                                @endif
                                | Server: <code class="px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">{{ config('services.whatsapp_gateway.url') }}</code>
                            </p>
                        </div>
                    @else
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400">
                            <x-heroicon-o-exclamation-triangle class="w-7 h-7" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Status Perangkat: Belum Terhubung</h3>
                                <span class="px-2.5 py-0.5 text-xs font-semibold text-amber-700 bg-amber-100 rounded-full dark:bg-amber-900 dark:text-amber-300">Perlu Scan QR</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                Perangkat <strong class="text-gray-800 dark:text-white">{{ config('services.whatsapp_gateway.device_id', 'rafatax') }}</strong> belum tersambung ke WhatsApp. Silakan klik tombol <strong>Scan QR Code</strong> di bawah untuk menghubungkan akun WhatsApp Anda.
                            </p>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if (!($gatewayStatus['connected'] ?? false))
                        <x-filament::button wire:click="fetchQrCode" color="success" icon="heroicon-o-qr-code" size="sm">
                            Scan QR Code
                        </x-filament::button>

                        <x-filament::button wire:click="reconnectAction" color="gray" icon="heroicon-o-arrow-path" size="sm">
                            Reconnect
                        </x-filament::button>
                    @endif

                    <x-filament::button wire:click="checkGatewayStatus" color="gray" icon="heroicon-o-arrow-path" size="sm">
                        Cek Status
                    </x-filament::button>

                    {{ $this->testSendAction }}

                    @if ($gatewayStatus['connected'] ?? false)
                        {{ $this->logoutDeviceAction }}
                    @endif
                </div>
            </div>

            {{-- Kotak QR Code Interaktif --}}
            @if ($isShowingQr && !empty($qrCodeBase64))
                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 p-6 rounded-xl text-center">
                    <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">Tautkan Perangkat WhatsApp</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $qrCodeMessage }}</p>

                    <div class="inline-block p-4 bg-white rounded-xl shadow-md border border-gray-200 dark:border-gray-600">
                        <img src="{{ $qrCodeBase64 }}" alt="WhatsApp Login QR Code" class="w-64 h-64 mx-auto object-contain" />
                    </div>

                    <div class="mt-4 max-w-md mx-auto text-left text-xs text-gray-600 dark:text-gray-400 space-y-1">
                        <p class="font-semibold text-gray-700 dark:text-gray-300">Cara Tautkan:</p>
                        <p>1. Buka aplikasi WhatsApp di HP Anda.</p>
                        <p>2. Ketuk ikon <strong>Titik Tiga (Menu)</strong> atau <strong>Pengaturan</strong> > <strong>Perangkat Tertaut (Linked Devices)</strong>.</p>
                        <p>3. Ketuk <strong>Tautkan Perangkat (Link a Device)</strong> lalu arahkan kamera ke QR Code di atas.</p>
                    </div>

                    <div class="mt-5 flex items-center justify-center gap-3">
                        <x-filament::button wire:click="checkGatewayStatus" color="primary" icon="heroicon-o-check">
                            Saya Sudah Scan (Periksa Status)
                        </x-filament::button>

                        <x-filament::button wire:click="fetchQrCode" color="gray" icon="heroicon-o-arrow-path">
                            Perbarui QR Code
                        </x-filament::button>

                        <x-filament::button wire:click="hideQrCode" color="danger" variant="outlined" icon="heroicon-o-x-mark">
                            Tutup
                        </x-filament::button>
                    </div>
                </div>
            @endif

            @if (!empty($gatewayStatus['data']))
                <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Detail Perangkat Gateway:</h4>
                    <div class="p-3 font-mono text-xs text-gray-800 bg-gray-50 dark:bg-gray-900 dark:text-gray-200 rounded-lg overflow-x-auto">
                        <pre>{{ json_encode($gatewayStatus['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            @endif
    </div>
</x-filament-panels::page>
