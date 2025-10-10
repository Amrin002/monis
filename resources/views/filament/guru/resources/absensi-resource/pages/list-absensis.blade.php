{{-- resources/views/filament/guru/resources/absensi-resource/pages/list-absensis.blade.php --}}

<x-filament-panels::page>
    {{-- Absensi Hari Ini --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-calendar class="w-5 h-5 text-primary-500" />
                <span>Absensi Hari Ini - {{ now()->format('d F Y') }}</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Daftar absensi untuk hari ini. Klik "View" untuk melihat detail atau "Edit" untuk mengubah.
        </x-slot>

        <x-slot name="headerEnd">
            @if ($this->getAbsensiHariIni()->count() > 0)
                <x-filament::button wire:click="kirimSemuaKeWaliKelas" color="primary" size="sm"
                    icon="heroicon-o-paper-airplane">
                    Kirim Semua ke Wali Kelas
                </x-filament::button>
            @endif
        </x-slot>

        @php
            $absensiHariIni = $this->getAbsensiHariIni();
        @endphp

        @if ($absensiHariIni->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Kelas
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Mata Pelajaran
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Jam Ke-
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Waktu
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Kehadiran
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                % Hadir
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($absensiHariIni as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="font-semibold text-gray-950 dark:text-white">{{ $item['kelas'] }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-950 dark:text-white">
                                    {{ $item['mapel'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <x-filament::badge color="info" size="sm">
                                        Jam {{ $item['jam_ke'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-950 dark:text-white">
                                    {{ $item['jam_mulai'] }} - {{ $item['jam_selesai'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-gray-950 dark:text-white">
                                    {{ $item['jumlah_hadir'] }} / {{ $item['total_siswa'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <x-filament::badge
                                        :color="$item['persentase'] >= 80 ? 'success' : ($item['persentase'] >= 60 ? 'warning' : 'danger')"
                                        size="sm">
                                        {{ $item['persentase'] }}%
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-filament::button size="xs" color="info" icon="heroicon-o-eye" tag="a"
                                            :href="route('filament.guru.resources.absensis.detail', ['jadwal' => $item['jadwal_id'], 'tanggal' => $item['tanggal']])">
                                            View
                                        </x-filament::button>

                                        <x-filament::button size="xs" color="warning" icon="heroicon-o-pencil"
                                            tag="a"
                                            :href="route('filament.guru.resources.absensis.input', ['jadwal_id' => $item['jadwal_id'], 'tanggal' => $item['tanggal']])">
                                            Edit
                                        </x-filament::button>

                                        <x-filament::button size="xs" color="primary"
                                            icon="heroicon-o-paper-airplane"
                                            wire:click="kirimKeWaliKelas({{ $item['jadwal_id'] }}, '{{ $item['tanggal'] }}', '{{ $item['kelas'] }}', '{{ $item['mapel'] }}')">
                                            Kirim
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div
                class="flex flex-col items-center justify-center py-12 text-center bg-gray-50 dark:bg-white/5 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                <x-heroicon-o-inbox class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" />
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    Belum ada absensi hari ini
                </h3>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    Klik tombol "Input Absensi" di atas untuk memulai.
                </p>
            </div>
        @endif
    </x-filament::section>

    {{-- Riwayat Absensi Sebelumnya --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                <span>Riwayat Absensi Sebelumnya</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Daftar absensi hari-hari sebelumnya (50 terakhir).
        </x-slot>

        @php
            $absensiSebelumnya = $this->getAbsensiSebelumnya();
        @endphp

        @if ($absensiSebelumnya->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Tanggal
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Kelas
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Mata Pelajaran
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Hari
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Jam Ke-
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Kehadiran
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                % Hadir
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border-b border-gray-200 dark:border-white/10">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($absensiSebelumnya as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-950 dark:text-white">
                                    {{ \Carbon\Carbon::parse($item['tanggal'])->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="font-semibold text-gray-950 dark:text-white">{{ $item['kelas'] }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-950 dark:text-white">
                                    {{ $item['mapel'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-950 dark:text-white">
                                    {{ $item['hari'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <x-filament::badge color="gray" size="sm">
                                        {{ $item['jam_ke'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-gray-950 dark:text-white">
                                    {{ $item['jumlah_hadir'] }} / {{ $item['total_siswa'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <x-filament::badge
                                        :color="$item['persentase'] >= 80 ? 'success' : ($item['persentase'] >= 60 ? 'warning' : 'danger')"
                                        size="sm">
                                        {{ $item['persentase'] }}%
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-filament::button size="xs" color="info" icon="heroicon-o-eye" tag="a"
                                            :href="route('filament.guru.resources.absensis.detail', ['jadwal' => $item['jadwal_id'], 'tanggal' => $item['tanggal']])">
                                            View
                                        </x-filament::button>

                                        <x-filament::button size="xs" color="warning" icon="heroicon-o-pencil"
                                            tag="a"
                                            :href="route('filament.guru.resources.absensis.input', ['jadwal_id' => $item['jadwal_id'], 'tanggal' => $item['tanggal']])">
                                            Edit
                                        </x-filament::button>

                                        <x-filament::button size="xs" color="primary"
                                            icon="heroicon-o-paper-airplane"
                                            wire:click="kirimKeWaliKelas({{ $item['jadwal_id'] }}, '{{ $item['tanggal'] }}', '{{ $item['kelas'] }}', '{{ $item['mapel'] }}')">
                                            Kirim
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div
                class="flex flex-col items-center justify-center py-12 text-center bg-gray-50 dark:bg-white/5 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                <x-heroicon-o-inbox class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" />
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    Belum ada riwayat absensi
                </h3>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    Riwayat absensi akan muncul setelah Anda melakukan absensi.
                </p>
            </div>
        @endif
    </x-filament::section>

    {{-- Modal Konfirmasi Kirim Single --}}
    <x-filament::modal id="modal-kirim-single" width="md">
        <x-slot name="heading">
            Konfirmasi Kirim Absensi
        </x-slot>

        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Anda akan mengirim absensi dengan detail:
            </p>

            <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Kelas:</span>
                    <span class="text-gray-950 dark:text-white" x-text="$wire.modalData.kelas"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Mata Pelajaran:</span>
                    <span class="text-gray-950 dark:text-white" x-text="$wire.modalData.mapel"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Tanggal:</span>
                    <span class="text-gray-950 dark:text-white" x-text="$wire.modalData.tanggal"></span>
                </div>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Apakah Anda yakin ingin mengirim absensi ini ke Wali Kelas?
            </p>
        </div>

        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'modal-kirim-single' })">
                Batal
            </x-filament::button>

            <x-filament::button color="primary" wire:click="prosesKirimSingle">
                Ya, Kirim
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Modal Konfirmasi Kirim Semua --}}
    <x-filament::modal id="modal-kirim-semua" width="lg">
        <x-slot name="heading">
            Konfirmasi Kirim Semua Absensi
        </x-slot>

        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Anda akan mengirim <span class="font-semibold">{{ $absensiHariIni->count() }} absensi</span> ke Wali
                Kelas dengan detail:
            </p>

            <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-4 max-h-60 overflow-y-auto space-y-2">
                @foreach ($absensiHariIni as $item)
                    <div class="flex justify-between items-center text-sm py-2 border-b border-gray-200 dark:border-white/10 last:border-0">
                        <div>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $item['kelas'] }}</span>
                            <span class="text-gray-600 dark:text-gray-400"> - {{ $item['mapel'] }}</span>
                        </div>
                        <x-filament::badge size="xs" :color="$item['persentase'] >= 80 ? 'success' : ($item['persentase'] >= 60 ? 'warning' : 'danger')">
                            {{ $item['persentase'] }}%
                        </x-filament::badge>
                    </div>
                @endforeach
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Apakah Anda yakin ingin mengirim semua absensi hari ini ke Wali Kelas masing-masing?
            </p>
        </div>

        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'modal-kirim-semua' })">
                Batal
            </x-filament::button>

            <x-filament::button color="primary" wire:click="prosesKirimSemua">
                Ya, Kirim Semua
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
