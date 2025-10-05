<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Guru;
use App\Models\OrangTua;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    // Deklarasikan property di sini
    protected $linkedGuruId;
    protected $linkedOrangTuaId;
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record;

        // Isi field linked berdasarkan role
        if ($user->role === 'guru') {
            $guru = Guru::where('user_id', $user->id)->first();
            $data['linked_guru_id'] = $guru?->id;
        } elseif ($user->role === 'orangtua') {
            $orangTua = OrangTua::where('user_id', $user->id)->first();
            $data['linked_orangtua_id'] = $orangTua?->id;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Simpan linked_id untuk diproses setelah user diupdate
        $this->linkedGuruId = $data['linked_guru_id'] ?? null;
        $this->linkedOrangTuaId = $data['linked_orangtua_id'] ?? null;

        // Hapus field temporary dari data yang akan disimpan
        unset($data['linked_guru_id']);
        unset($data['linked_orangtua_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;

        // Update linking untuk guru
        if ($user->role === 'guru') {
            // Reset user_id lama
            Guru::where('user_id', $user->id)->update(['user_id' => null]);

            // Set user_id baru
            if ($this->linkedGuruId) {
                Guru::where('id', $this->linkedGuruId)
                    ->update(['user_id' => $user->id]);
            }
        }

        // Update linking untuk orang tua
        if ($user->role === 'orangtua') {
            // Reset user_id lama
            OrangTua::where('user_id', $user->id)->update(['user_id' => null]);

            // Set user_id baru
            if ($this->linkedOrangTuaId) {
                OrangTua::where('id', $this->linkedOrangTuaId)
                    ->update(['user_id' => $user->id]);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    $user = $this->record;
                    if ($user->role === 'guru') {
                        Guru::where('user_id', $user->id)->update(['user_id' => null]);
                    } elseif ($user->role === 'orangtua') {
                        OrangTua::where('user_id', $user->id)->update(['user_id' => null]);
                    }
                }),
        ];
    }
}
