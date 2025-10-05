<?php
// app/Filament/Guru/Pages/Dashboard.php

namespace App\Filament\Guru\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.guru.pages.dashboard';


    public function getTitle(): string
    {
        return 'Dashboard Guru';
    }

    public function getHeading(): string
    {
        $guru = Auth::user()->guru;

        if (!$guru) {
            return 'Dashboard Guru';
        }

        return 'Selamat Datang, ' . $guru->nama;
    }

    public function getSubheading(): ?string
    {
        $guru = Auth::user()->guru;

        if (!$guru) {
            return null;
        }

        $roles = [];

        if ($guru->isWaliKelas()) {
            $roles[] = 'Wali Kelas';
        }

        if ($guru->isGuruMapel()) {
            $roles[] = 'Guru Mata Pelajaran';
        }

        if (empty($roles)) {
            return 'NIP: ' . $guru->nip;
        }

        return 'NIP: ' . $guru->nip . ' | ' . implode(' & ', $roles);
    }

    protected function getHeaderWidgets(): array
    {
        $user = Auth::user();
        $guru = $user->guru;
        $widgets = [];

        // Widget pengenal untuk semua guru
        $widgets[] = \App\Filament\Guru\Widgets\WelcomeWidget::class;

        // Widget untuk Wali Kelas
        if ($guru && $guru->isWaliKelas()) {
            // $widgets[] = \App\Filament\Guru\Widgets\WaliKelasStatsWidget::class;
            // $widgets[] = \App\Filament\Guru\Widgets\DaftarSiswaKelasWidget::class;
        }

        // Widget untuk Guru Mapel
        if ($guru && $guru->isGuruMapel()) {
            // $widgets[] = \App\Filament\Guru\Widgets\GuruMapelStatsWidget::class;
            // $widgets[] = \App\Filament\Guru\Widgets\DaftarMapelWidget::class;
        }

        return $widgets;
    }
}
