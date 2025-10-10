<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Info Card --}}
        <x-filament::section>
            <x-slot name="heading">
                Informasi Absensi
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal</p>
                    <p class="text-base font-semibold">{{ \Carbon\Carbon::parse($tanggal)->format('d F Y') }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kelas</p>
                    <p class="text-base font-semibold">{{ $jadwalInfo->kelas->nama ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</p>
                    <p class="text-base font-semibold">{{ $jadwalInfo->mapel->nama_matapelajaran ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jam Pelajaran</p>
                    <p class="text-base font-semibold">
                        {{ $jadwalInfo->hari ?? '-' }},
                        {{ \Carbon\Carbon::parse($jadwalInfo->jam_mulai)->format('H:i') ?? '-' }} -
                        {{ \Carbon\Carbon::parse($jadwalInfo->jam_selesai)->format('H:i') ?? '-' }}
                        (Jam ke-{{ $jadwalInfo->jam_ke ?? '-' }})
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Guru</p>
                    <p class="text-base font-semibold">{{ $jadwalInfo->mapel->guru->nama ?? '-' }}</p>
                </div>
            </div>

            {{-- ====================================================== --}}
            {{-- BAGIAN YANG DIUBAH (START) --}}
            {{-- ====================================================== --}}
            @php
                $stats = \App\Models\Absensi::where('jadwal_id', $jadwalId)
                    ->whereDate('tanggal', $tanggal)
                    ->selectRaw('
                                        COUNT(*) as total,
                                        SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir,
                                        SUM(CASE WHEN status = "izin" THEN 1 ELSE 0 END) as izin,
                                        SUM(CASE WHEN status = "sakit" THEN 1 ELSE 0 END) as sakit,
                                        SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) as alpa
                                    ')
                    ->first();
            @endphp
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white mb-4">
                    Ringkasan Kehadiran
                </h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
                    {{-- Card Total --}}
                    <div class="p-4 bg-gray-100 rounded-lg dark:bg-gray-800">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Siswa</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            {{ $stats->total ?? 0 }}</p>
                    </div>
                    {{-- Card Hadir --}}
                    <div class="p-4 bg-green-100 rounded-lg dark:bg-green-800/20">
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">Hadir</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-green-600 dark:text-green-400">
                            {{ $stats->hadir ?? 0 }}</p>
                    </div>
                    {{-- Card Izin --}}
                    <div class="p-4 bg-yellow-100 rounded-lg dark:bg-yellow-800/20">
                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Izin</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600 dark:text-yellow-400">
                            {{ $stats->izin ?? 0 }}</p>
                    </div>
                    {{-- Card Sakit --}}
                    <div class="p-4 bg-yellow-100 rounded-lg dark:bg-yellow-800/20">
                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Sakit</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600 dark:text-yellow-400">
                            {{ $stats->sakit ?? 0 }}</p>
                    </div>
                    {{-- Card Alpa --}}
                    <div class="p-4 bg-red-100 rounded-lg dark:bg-red-800/20">
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">Alpa</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-red-600 dark:text-red-400">
                            {{ $stats->alpa ?? 0 }}</p>
                    </div>
                </div>
            </div>
            {{-- ====================================================== --}}
            {{-- BAGIAN YANG DIUBAH (END) --}}
            {{-- ====================================================== --}}
        </x-filament::section>

        {{-- Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Daftar Kehadiran Siswa
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
