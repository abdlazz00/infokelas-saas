<x-filament::section class="bg-warning-50 border border-warning-200 dark:bg-warning-900/20 dark:border-warning-600">
    <div class="flex items-start gap-4">
        {{-- Icon Segitiga Peringatan --}}
        <div class="p-2 bg-warning-100 rounded-lg dark:bg-warning-900">
            <x-heroicon-m-exclamation-triangle class="w-6 h-6 text-warning-600 dark:text-warning-500" />
        </div>

        <div class="flex-1">
            <h3 class="text-lg font-bold text-warning-800 dark:text-warning-500">
                Peringatan Masa Aktif Kelas
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                Beberapa kelas Anda akan segera berakhir. Mohon segera lakukan perpanjangan agar akses tidak terkunci.
            </p>

            {{-- List Kelas yang mau Expired --}}
            <ul class="mt-3 space-y-2">
                @foreach($expiringClasses as $class)
                    <li class="flex items-center gap-2 text-sm font-medium text-warning-900 dark:text-warning-400 bg-white/50 dark:bg-white/5 p-2 rounded">
                        <span class="w-2 h-2 rounded-full bg-warning-500"></span>
                        Kelas: <strong>{{ $class->name }}</strong>
                        <span class="text-xs opacity-75 ml-1">
                            (Berakhir: {{ $class->expired_at->format('d M Y') }})
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-filament::section>
