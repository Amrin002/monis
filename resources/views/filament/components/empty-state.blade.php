{{-- resources/views/filament/components/empty-state.blade.php --}}
<div class="flex flex-col items-center justify-center py-12 px-4">
    <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-6 mb-4">
        <svg class="w-16 h-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
            </path>
        </svg>
    </div>

    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
        {{ $title ?? 'Tidak Ada Data' }}
    </h3>

    <p class="text-sm text-gray-600 dark:text-gray-400 text-center max-w-md">
        {{ $message ?? 'Belum ada data yang tersedia saat ini.' }}
    </p>

    @if(isset($action))
        <div class="mt-6">
            {{ $action }}
        </div>
    @endif
</div>
