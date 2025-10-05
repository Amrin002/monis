{{-- resources/views/filament/guru/resources/absensi-kelas-resource/pages/input-absensi-kelas.blade.php --}}

<x-filament-panels::page>
    <div class="mb-4">
        <div class="bg-primary-50 dark:bg-primary-900/20 border-l-4 border-primary-500 p-4 rounded-r-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-primary-800 dark:text-primary-200">
                        Kelas: <span class="font-bold">{{ $this->getKelasName() }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit="simpanAbsensi">
        {{ $this->form }}

        @if(count($siswaList) > 0)
            <div class="mt-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Daftar Siswa ({{ count($siswaList) }} orang)
                    </h3>
                    <x-filament::button type="button" wire:click="setSemuaHadir" color="success" size="sm">
                        Tandai Semua Hadir
                    </x-filament::button>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">
                                    No
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">
                                    NIS
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Nama Siswa
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status Kehadiran
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-64">
                                    Keterangan
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($siswaList as $index => $siswa)
                                <tr class="{{ $siswa['exists'] ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}">
                                    <td
                                        class="px-4 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-gray-100">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $siswa['nis'] }}
                                    </td>
                                    <td
                                        class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $siswa['nama'] }}
                                        @if($siswa['exists'])
                                            <span
                                                class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                                                Sudah diabsen
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap gap-3 justify-center">
                                            <label
                                                class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 px-2 py-1 rounded transition">
                                                <input type="radio" wire:model.live="siswaList.{{ $index }}.status"
                                                    value="hadir"
                                                    class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 dark:border-gray-500">
                                                <span
                                                    class="ml-1.5 text-sm font-medium text-gray-900 dark:text-gray-100">Hadir</span>
                                            </label>

                                            <label
                                                class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 px-2 py-1 rounded transition">
                                                <input type="radio" wire:model.live="siswaList.{{ $index }}.status" value="izin"
                                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-500">
                                                <span
                                                    class="ml-1.5 text-sm font-medium text-gray-900 dark:text-gray-100">Izin</span>
                                            </label>

                                            <label
                                                class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 px-2 py-1 rounded transition">
                                                <input type="radio" wire:model.live="siswaList.{{ $index }}.status"
                                                    value="sakit"
                                                    class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 dark:border-gray-500">
                                                <span
                                                    class="ml-1.5 text-sm font-medium text-gray-900 dark:text-gray-100">Sakit</span>
                                            </label>

                                            <label
                                                class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 px-2 py-1 rounded transition">
                                                <input type="radio" wire:model.live="siswaList.{{ $index }}.status" value="alpa"
                                                    class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 dark:border-gray-500">
                                                <span
                                                    class="ml-1.5 text-sm font-medium text-gray-900 dark:text-gray-100">Alpa</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <input type="text" wire:model.live="siswaList.{{ $index }}.keterangan"
                                            placeholder="Keterangan (opsional)"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-filament::button type="button" color="gray" tag="a"
                        href="{{ \App\Filament\Guru\Resources\AbsensiKelasResource::getUrl('index') }}">
                        Batal
                    </x-filament::button>

                    <x-filament::button type="submit" color="warning">
                        Simpan Absensi
                    </x-filament::button>
                </div>
            </div>
        @else
            <div
                class="mt-6 text-center text-gray-500 dark:text-gray-400 py-12 bg-gray-50 dark:bg-gray-800/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <p class="mt-2 text-sm font-medium">Tidak ada siswa di kelas Anda</p>
            </div>
        @endif
    </form>
</x-filament-panels::page>
