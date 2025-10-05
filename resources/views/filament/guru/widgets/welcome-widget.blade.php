<x-filament-widgets::widget>
    @php
        $data = $this->getGuruData();
    @endphp

    <x-filament::section>
        <div class="space-y-6">
            {{-- Header Info --}}
            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <div class="w-24 h-24 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $data['nama'] }}</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">NIP: {{ $data['nip'] }}</p>
            </div>

            {{-- Role Badges --}}
            <div class="flex justify-center gap-2 flex-wrap">
                @if($data['is_wali_kelas'])
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Wali Kelas
                    </span>
                @endif

                @if($data['is_guru_mapel'])
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Guru Mata Pelajaran
                    </span>
                @endif

                @if(!$data['is_wali_kelas'] && !$data['is_guru_mapel'])
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Role Belum Ditentukan
                    </span>
                @endif
            </div>

            {{-- Divider --}}
            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            {{-- Detail Informasi --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Info Wali Kelas --}}
                @if($data['is_wali_kelas'])
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <h3 class="font-semibold text-green-900 dark:text-green-100 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Informasi Wali Kelas
                        </h3>
                        @if($data['kelas_wali'])
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-green-700 dark:text-green-300">Kelas:</span>
                                    <span class="font-semibold text-green-900 dark:text-green-100">{{ $data['kelas_wali']->nama }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-700 dark:text-green-300">Jumlah Siswa:</span>
                                    <span class="font-semibold text-green-900 dark:text-green-100">{{ $data['kelas_wali']->siswas->count() ?? 0 }} Siswa</span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-green-700 dark:text-green-300">Belum ada kelas yang dibimbing</p>
                        @endif
                    </div>
                @endif

                {{-- Info Guru Mapel --}}
                @if($data['is_guru_mapel'])
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            Mata Pelajaran
                        </h3>
                        @if($data['mapels']->count() > 0)
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between mb-2">
                                    <span class="text-blue-700 dark:text-blue-300">Total Mapel:</span>
                                    <span class="font-semibold text-blue-900 dark:text-blue-100">{{ $data['mapels']->count() }} Mapel</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach($data['mapels']->take(3) as $mapel)
                                        <div class="flex items-center text-blue-700 dark:text-blue-300">
                                            <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ $mapel->nama_matapelajaran }}</span>
                                        </div>
                                    @endforeach
                                    @if($data['mapels']->count() > 3)
                                        <div class="text-blue-600 dark:text-blue-400 text-xs mt-1">
                                            +{{ $data['mapels']->count() - 3 }} mapel lainnya
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-blue-700 dark:text-blue-300">Belum ada mata pelajaran yang diampu</p>
                        @endif
                    </div>
                @endif

                {{-- Jika tidak ada role --}}
                @if(!$data['is_wali_kelas'] && !$data['is_guru_mapel'])
                    <div class="col-span-full bg-gray-50 dark:bg-gray-800 rounded-lg p-6 text-center border border-gray-200 dark:border-gray-700">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Role Belum Ditentukan</h3>
                        <p class="text-gray-600 dark:text-gray-400">Silakan hubungi admin untuk mengatur role Anda sebagai Wali Kelas atau Guru Mata Pelajaran</p>
                    </div>
                @endif
            </div>

            {{-- Footer Info --}}
            <div class="text-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Login sebagai: <span class="font-semibold">{{ auth()->user()->email }}</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    Terakhir login: {{ auth()->user()->last_login_at?->diffForHumans() ?? 'Baru saja' }}
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
