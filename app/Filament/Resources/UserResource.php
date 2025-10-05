<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Guru;
use App\Models\OrangTua;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen Pengguna';
    protected static ?string $navigationLabel = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'guru' => 'Guru',
                        'orangtua' => 'Orang Tua',
                    ])
                    ->live() // Ganti dari reactive() ke live()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Reset pilihan saat role berubah
                        $set('linked_guru_id', null);
                        $set('linked_orangtua_id', null);
                        $set('name', ''); // Reset nama juga
                    }),

                Forms\Components\Select::make('linked_guru_id')
                    ->label('Pilih Guru')
                    ->options(function (?string $operation, $record) {
                        // Ambil guru yang belum punya akun (user_id = null)
                        // Atau guru yang sudah di-link dengan user ini (saat edit)
                        return Guru::where(function ($query) use ($record) {
                            $query->whereNull('user_id');
                            if ($record) {
                                $query->orWhere('user_id', $record->id);
                            }
                        })->pluck('nama', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live() // Ganti dari reactive() ke live()
                    ->visible(fn($get) => $get('role') === 'guru')
                    ->required(fn($get) => $get('role') === 'guru')
                    ->helperText('Pilih data guru yang akan dihubungkan dengan akun ini.')
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Otomatis isi field name dengan nama guru yang dipilih
                        if ($state) {
                            $guru = Guru::find($state);
                            if ($guru) {
                                $set('name', $guru->nama);
                            }
                        }
                    }),

                Forms\Components\Select::make('linked_orangtua_id')
                    ->label('Pilih Orang Tua')
                    ->options(function (?string $operation, $record) {
                        // Ambil orang tua yang belum punya akun (user_id = null)
                        // Atau orang tua yang sudah di-link dengan user ini (saat edit)
                        return OrangTua::where(function ($query) use ($record) {
                            $query->whereNull('user_id');
                            if ($record) {
                                $query->orWhere('user_id', $record->id);
                            }
                        })->pluck('nama', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live() // Ganti dari reactive() ke live()
                    ->visible(fn($get) => $get('role') === 'orangtua')
                    ->required(fn($get) => $get('role') === 'orangtua')
                    ->helperText('Pilih data orang tua yang akan dihubungkan dengan akun ini.')
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Otomatis isi field name dengan nama orang tua yang dipilih
                        if ($state) {
                            $orangTua = OrangTua::find($state);
                            if ($orangTua) {
                                $set('name', $orangTua->nama);
                            }
                        }
                    }),

                Forms\Components\Placeholder::make('info_admin')
                    ->label('Informasi')
                    ->content('Akun admin tidak perlu dihubungkan dengan data guru atau orang tua.')
                    ->visible(fn($get) => $get('role') === 'admin'),

                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255)
                    ->helperText(
                        fn($get) =>
                        $get('role') === 'admin'
                            ? 'Masukkan nama untuk akun admin.'
                            : 'Nama akan otomatis terisi saat memilih Guru/Orang Tua.'
                    ),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->maxLength(255)
                    ->helperText('Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'primary' => 'admin',
                        'success' => 'guru',
                        'warning' => 'orangtua',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'guru' => 'Guru',
                        'orangtua' => 'Orang Tua',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('linked_name')
                    ->label('Terhubung Dengan')
                    ->getStateUsing(function (User $record) {
                        if ($record->role === 'guru') {
                            $guru = Guru::where('user_id', $record->id)->first();
                            return $guru ? $guru->nama : '-';
                        } elseif ($record->role === 'orangtua') {
                            $orangTua = OrangTua::where('user_id', $record->id)->first();
                            return $orangTua ? $orangTua->nama : '-';
                        }
                        return '-';
                    })
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'guru' => 'Guru',
                        'orangtua' => 'Orang Tua',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        // Set user_id jadi null di tabel guru/orang_tuas sebelum delete
                        if ($record->role === 'guru') {
                            Guru::where('user_id', $record->id)->update(['user_id' => null]);
                        } elseif ($record->role === 'orangtua') {
                            OrangTua::where('user_id', $record->id)->update(['user_id' => null]);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->role === 'guru') {
                                    Guru::where('user_id', $record->id)->update(['user_id' => null]);
                                } elseif ($record->role === 'orangtua') {
                                    OrangTua::where('user_id', $record->id)->update(['user_id' => null]);
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),

        ];
    }
}
