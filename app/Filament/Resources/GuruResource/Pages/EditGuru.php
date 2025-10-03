<?php

namespace App\Filament\Resources\GuruResource\Pages;

use App\Filament\Resources\GuruResource;
use App\Models\Kelas;
use App\Models\Mapel;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGuru extends EditRecord
{
    protected static string $resource = GuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    // Lepaskan semua relasi sebelum hapus
                    Kelas::where('wali_guru_id', $record->id)
                        ->update(['wali_guru_id' => null]);

                    Mapel::where('guru_id', $record->id)
                        ->update(['guru_id' => null]);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pre-populate kelas yang diampu sebagai wali
        $kelas = Kelas::where('wali_guru_id', $this->record->id)->first();
        if ($kelas) {
            $data['kelas_id_wali'] = $kelas->id;
        }

        // Pre-populate mapel yang diampu
        $mapelIds = Mapel::where('guru_id', $this->record->id)->pluck('id')->toArray();
        if (!empty($mapelIds)) {
            $data['mapel_ids'] = $mapelIds;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. Ekstrak data relasi
        $kelasId = $data['kelas_id_wali'] ?? null;
        $mapelIds = $data['mapel_ids'] ?? [];

        // 2. Hapus field virtual dari data
        unset($data['kelas_id_wali']);
        unset($data['mapel_ids']);

        // 3. Update record guru
        $record->update($data);

        // 4. Reset semua relasi kelas yang sebelumnya
        Kelas::where('wali_guru_id', $record->id)
            ->update(['wali_guru_id' => null]);

        // 5. Update relasi kelas baru jika masih wali kelas
        if ($record->is_wali_kelas && $kelasId) {
            Kelas::where('id', $kelasId)->update(['wali_guru_id' => $record->id]);
        }

        // 6. Reset semua relasi mapel yang sebelumnya
        Mapel::where('guru_id', $record->id)
            ->update(['guru_id' => null]);

        // 7. Update relasi mapel baru jika masih guru mapel
        if ($record->is_guru_mapel && !empty($mapelIds)) {
            Mapel::whereIn('id', $mapelIds)->update(['guru_id' => $record->id]);
        }

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Guru Berhasil Diperbarui')
            ->body('Data guru dan penugasannya telah berhasil diperbarui.');
    }
}
