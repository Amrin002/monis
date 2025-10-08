<?php
// app/Notifications/LaporanMapelTerkirimNotification.php

namespace App\Notifications;

use App\Models\Laporan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon; // 👈 IMPORT CARBON

class LaporanMapelTerkirimNotification extends Notification
{
    use Queueable;

    protected $laporan;

    public function __construct(Laporan $laporan)
    {
        $this->laporan = $laporan;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'Laporan Baru dari Guru Mapel',
            'body' => $this->laporan->guru->nama . ' mengirimkan laporan ' .
                $this->laporan->jadwal->mapel->nama_matapelajaran .
                ' untuk siswa ' . $this->laporan->siswa->nama,
            'icon' => 'heroicon-o-academic-cap',
            'iconColor' => 'info',
            'duration' => 'persistent',

            // Data tambahan
            'laporan_id' => $this->laporan->id,
            'siswa_id' => $this->laporan->siswa_id,
            'siswa_nama' => $this->laporan->siswa->nama,
            'guru_nama' => $this->laporan->guru->nama,
            'mapel_nama' => $this->laporan->jadwal->mapel->nama_matapelajaran,
            'tanggal' => Carbon::parse($this->laporan->tanggal)->format('d/m/Y'), // 👈 FIX INI

            // Action URL
            'actions' => [
                [
                    'label' => 'Lihat Laporan',
                    'url' => url("/guru/laporan-siswa/{$this->laporan->id}"),
                    // 'url' => route('filament.guru.resources.laporan-siswas.view', $this->laporan->id),
                ],
            ],
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
