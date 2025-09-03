<x-filament-panels::page>
    <x-filament::card>
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900">Informasi Profile</h2>
                <p class="mt-1 text-sm text-gray-600">Informasi pribadi Anda</p>
            </div>

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama</label>
                    <div class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md">
                        {{ $user->name ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md">
                        {{ $user->email ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <div class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md">
                        @if (isset($user) && $user->roles)
                            @foreach ($user->roles as $role)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        @else
                            <span class="text-gray-500">Tidak ada role</span>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <div class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md">
                        @if (isset($user) && $user->is_verified)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Terverifikasi
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Belum Terverifikasi
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if (isset($user) && method_exists($user, 'hasRole') && $user->hasRole('admin'))
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Anda memiliki akses ke <strong>Panel Admin</strong>.
                                <a href="/admin" class="font-medium underline hover:text-blue-600">Klik di sini</a>
                                untuk mengakses panel admin.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament-panels::page>
