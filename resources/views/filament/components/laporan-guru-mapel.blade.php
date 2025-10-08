{{-- resources/views/filament/components/laporan-guru-mapel.blade.php --}}

<div class="space-y-4">
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-2">{{ $siswa->nama }} ({{ $siswa->nis }})</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Kelas: {{ $siswa->kelas->nama ?? '-' }}</p>
    </div>

    <div class="space-y-3">
        @forelse($laporans as $laporan)
            <div class="border dark:border-gray-700 rounded-lg p-4">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-semibold text-base">{{ $laporan->jadwal->mapel->nama_matapelajaran ?? '-' }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Guru: {{ $laporan->guru->nama ?? '-' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium">{{ $laporan->tanggal->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-500">{{ $laporan->jadwal->hari ?? '-' }}</p>
                    </div>
                </div>
                <div class="prose dark:prose-invert max-w-none text-sm">
                    {!! $laporan->keterangan !!}
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-gray-500">
                <p>Belum ada laporan dari guru mata pelajaran</p>
            </div>
        @endforelse
    </div>
</div>
