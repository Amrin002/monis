<?php

namespace App\Filament\Resources\GuruResource\Pages;

use App\Filament\Resources\GuruResource;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGuru extends CreateRecord
{
    protected static string $resource = GuruResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Ekstrak data relasi
        $kelasId = $data['kelas_id_wali'] ?? null;
        $mapelIds = $data['mapel_ids'] ?? [];

        // 2. Hapus field virtual dari data
        unset($data['kelas_id_wali']);
        unset($data['mapel_ids']);

        // 3. Buat record guru
        $guru = Guru::create($data);

        // 4. Update relasi kelas jika dipilih sebagai wali kelas
        if ($guru->is_wali_kelas && $kelasId) {
            Kelas::where('id', $kelasId)->update(['wali_guru_id' => $guru->id]);
        }

        // 5. Update relasi mapel jika dipilih sebagai guru mapel
        if ($guru->is_guru_mapel && !empty($mapelIds)) {
            Mapel::whereIn('id', $mapelIds)->update(['guru_id' => $guru->id]);
        }

        return $guru;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Guru Berhasil Dibuat')
            ->body('Data guru telah berhasil disimpan beserta penugasannya.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
