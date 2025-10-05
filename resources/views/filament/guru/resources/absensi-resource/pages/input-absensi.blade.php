{{-- resources/views/filament/guru/resources/absensi-resource/pages/input-absensi.blade.php --}}

<x-filament-panels::page>
    <form wire:submit="simpanAbsensi">
        {{ $this->form }}

        @if(count($siswaList) > 0)
            <div class="mt-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold dark:text-white">Daftar Siswa ({{ count($siswaList) }} orang)</h3>
                    <x-filament::button type="button" wire:click="setSemuaHadir" color="success" size="sm">
                        Tandai Semua Hadir
                    </x-filament::button>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-600">
                    {{-- PERUBAHAN 1: Menambahkan kelas 'w-full' agar tabel memenuhi kontainer --}}
                    <table class="w-full min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    No</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    NIS</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Nama Siswa</th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status Kehadiran</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($siswaList as $index => $siswa)
                                <tr class="{{ $siswa['exists'] ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $siswa['nis'] }}
                                    </td>
                                    <td
                                        class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $siswa['nama'] }}
                                        @if($siswa['exists'])
                                            <span class="ml-2 text-xs text-yellow-600 dark:text-yellow-400">(Sudah diabsen)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex gap-4 justify-center">
                                            {{-- PERUBAHAN 2: Mengubah 'dark:text-gray-300' menjadi 'dark:text-gray-400' --}}
                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio" wire:model.live="siswaList.{{ $index }}.status"
                                                    value="hadir"
                                                    class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-400"> Hadir</span>
                                            </label>

                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio" wire:model.live="siswaList.{{ $index }}.status" value="izin"
                                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-400"> Izin</span>
                                            </label>

                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio" wire:model.live="siswaList.{{ $index }}.status"
                                                    value="sakit"
                                                    class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-400"> Sakit</span>
                                            </label>

                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio" wire:model.live="siswaList.{{ $index }}.status" value="alpa"
                                                    class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-400">Alpa</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <input type="text" wire:model.live="siswaList.{{ $index }}.keterangan"
                                            placeholder="Keterangan (opsional)"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-filament::button type="button" color="gray" tag="a"
                        href="{{ \App\Filament\Guru\Resources\AbsensiResource::getUrl('index') }}">
                        Batal
                    </x-filament::button>

                    <x-filament::button type="submit">
                        Simpan Absensi
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="mt-6 text-center text-gray-500 dark:text-gray-400 py-8">
                <p>Pilih jadwal pelajaran untuk menampilkan daftar siswa</p>
            </div>
        @endif
    </form>
</x-filament-panels::page>
