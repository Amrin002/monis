<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Guru;
use App\Models\OrangTua;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    // Deklarasikan property di sini
    protected $linkedGuruId;
    protected $linkedOrangTuaId;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Simpan linked_id untuk diproses setelah user dibuat
        $this->linkedGuruId = $data['linked_guru_id'] ?? null;
        $this->linkedOrangTuaId = $data['linked_orangtua_id'] ?? null;

        // Hapus field temporary dari data yang akan disimpan
        unset($data['linked_guru_id']);
        unset($data['linked_orangtua_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;

        // Link ke guru jika rolenya guru
        if ($user->role === 'guru' && $this->linkedGuruId) {
            Guru::where('id', $this->linkedGuruId)
                ->update(['user_id' => $user->id]);
        }

        // Link ke orang tua jika rolenya orangtua
        if ($user->role === 'orangtua' && $this->linkedOrangTuaId) {
            OrangTua::where('id', $this->linkedOrangTuaId)
                ->update(['user_id' => $user->id]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
