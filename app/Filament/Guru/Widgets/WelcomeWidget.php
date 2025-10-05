<?php
// app/Filament/Guru/Widgets/WelcomeWidget.php

namespace App\Filament\Guru\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WelcomeWidget extends Widget
{
    protected static string $view = 'filament.guru.widgets.welcome-widget';

    protected int | string | array $columnSpan = 'full';

    public function getGuruData(): array
    {
        $guru = Auth::user()->guru;

        if (!$guru) {
            return [
                'nama' => 'Guest',
                'nip' => '-',
                'is_wali_kelas' => false,
                'is_guru_mapel' => false,
                'kelas_wali' => null,
                'mapels' => collect([]),
            ];
        }

        return [
            'nama' => $guru->nama,
            'nip' => $guru->nip,
            'is_wali_kelas' => $guru->isWaliKelas(),
            'is_guru_mapel' => $guru->isGuruMapel(),
            'kelas_wali' => $guru->kelasWali,
            'mapels' => $guru->mapels,
        ];
    }
}
