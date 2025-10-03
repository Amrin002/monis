<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuruResource\Pages;
use App\Filament\Resources\GuruResource\RelationManagers;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GuruResource extends Resource
{
    protected static ?string $model = Guru::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen Data';
    protected static ?string $navigationLabel = 'Data Guru';
    protected static ?string $pluralModelLabel = 'Data Guru';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Guru')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('no_hp')
                            ->label('Nomor HP')
                            ->tel(),

                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Role dan Penugasan')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Checkbox::make('is_wali_kelas')
                                    ->label('Wali Kelas')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!$state) {
                                            $set('kelas_id_wali', null);
                                        }
                                    }),

                                Forms\Components\Checkbox::make('is_guru_mapel')
                                    ->label('Guru Mata Pelajaran')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!$state) {
                                            $set('mapel_ids', []);
                                        }
                                    }),
                            ])
                            ->columns(2),

                        Forms\Components\Select::make('kelas_id_wali')
                            ->label('Pilih Kelas (Sebagai Wali Kelas)')
                            ->options(function (?Model $record) {
                                return Kelas::query()
                                    ->where(function ($query) use ($record) {
                                        $query->whereNull('wali_guru_id');
                                        if ($record) {
                                            $query->orWhere('wali_guru_id', $record->id);
                                        }
                                    })
                                    ->pluck('nama', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get) => $get('is_wali_kelas') === true)
                            ->helperText('Pilih kelas yang akan diampu sebagai wali kelas'),

                        Forms\Components\Select::make('mapel_ids')
                            ->label('Pilih Mata Pelajaran')
                            ->options(function (?Model $record) {
                                return Mapel::query()
                                    ->where(function ($query) use ($record) {
                                        $query->whereNull('guru_id');
                                        if ($record) {
                                            $query->orWhere('guru_id', $record->id);
                                        }
                                    })
                                    ->pluck('nama_matapelajaran', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get) => $get('is_guru_mapel') === true)
                            ->helperText('Pilih mata pelajaran yang akan diampu'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('roles')
                    ->label('Role')
                    ->getStateUsing(function (Guru $record) {
                        $roles = [];
                        if ($record->is_guru_mapel) {
                            $roles[] = 'Guru Mapel';
                        }
                        if ($record->is_wali_kelas) {
                            $roles[] = 'Wali Kelas';
                        }
                        return empty($roles) ? '-' : implode(', ', $roles);
                    })
                    ->colors([
                        'success' => fn($state) => str_contains($state, 'Guru Mapel'),
                        'warning' => fn($state) => str_contains($state, 'Wali Kelas'),
                    ]),

                Tables\Columns\TextColumn::make('kelasWali.nama')
                    ->label('Wali Kelas')
                    ->default('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('mapels.nama_matapelajaran')
                    ->label('Mata Pelajaran')
                    ->default('-')
                    ->badge()
                    ->separator(', '),

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->default('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'wali_kelas' => 'Wali Kelas',
                        'guru_mapel' => 'Guru Mata Pelajaran',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'wali_kelas') {
                            return $query->where('is_wali_kelas', true);
                        } elseif ($data['value'] === 'guru_mapel') {
                            return $query->where('is_guru_mapel', true);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Guru $record) {
                        // Lepaskan relasi sebelum hapus
                        Kelas::where('wali_guru_id', $record->id)
                            ->update(['wali_guru_id' => null]);

                        Mapel::where('guru_id', $record->id)
                            ->update(['guru_id' => null]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                Kelas::where('wali_guru_id', $record->id)
                                    ->update(['wali_guru_id' => null]);

                                Mapel::where('guru_id', $record->id)
                                    ->update(['guru_id' => null]);
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
            'index' => Pages\ListGurus::route('/'),
            'create' => Pages\CreateGuru::route('/create'),
            'edit' => Pages\EditGuru::route('/{record}/edit'),
        ];
    }
}
