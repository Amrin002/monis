<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengumumanResource\Pages;
use App\Filament\Resources\PengumumanResource\RelationManagers;
use App\Models\Pengumuman;
use App\Models\Guru;
use App\Models\Kelas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PengumumanResource extends Resource
{
    protected static ?string $model = Pengumuman::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Komunikasi';
    protected static ?string $navigationLabel = 'Pengumuman';
    protected static ?string $pluralModelLabel = 'Pengumuman';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengumuman')
                    ->schema([
                        Forms\Components\TextInput::make('judul')
                            ->label('Judul Pengumuman')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pengumuman')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Forms\Components\Select::make('guru_id')
                            ->label('Dibuat oleh Guru')
                            ->options(Guru::all()->pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Opsional: Pilih guru pembuat pengumuman'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Target Penerima')
                    ->schema([
                        Forms\Components\Checkbox::make('is_umum')
                            ->label('Pengumuman Umum (Untuk Semua Kelas)')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('kelas_id', null);
                                }
                            })
                            ->helperText('Centang jika pengumuman ditujukan untuk semua kelas'),

                        Forms\Components\Select::make('kelas_id')
                            ->label('Kelas Spesifik')
                            ->options(Kelas::all()->pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->visible(fn(Get $get) => !$get('is_umum'))
                            ->helperText('Kosongkan untuk pengumuman umum, atau pilih kelas tertentu'),
                    ]),

                Forms\Components\Section::make('Isi Pengumuman')
                    ->schema([
                        Forms\Components\RichEditor::make('isi')
                            ->label('Isi Pengumuman')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'undo',
                                'redo',
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 40) {
                            return null;
                        }
                        return $state;
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('target')
                    ->label('Target')
                    ->getStateUsing(function (Pengumuman $record) {
                        return $record->kelas_id ? $record->kelas->nama : 'Umum (Semua Kelas)';
                    })
                    ->badge()
                    ->color(fn(string $state): string => $state === 'Umum (Semua Kelas)' ? 'success' : 'info')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('kelas_id', $direction);
                    }),

                Tables\Columns\TextColumn::make('guru.nama')
                    ->label('Pembuat')
                    ->default('Admin')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('isi')
                    ->label('Preview Isi')
                    ->html()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('umum')
                    ->label('Pengumuman Umum')
                    ->query(fn(Builder $query): Builder => $query->whereNull('kelas_id'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('kelas')
                    ->label('Filter Kelas')
                    ->relationship('kelas', 'nama')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Kelas'),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d M Y'))
                                ->removeField('dari_tanggal');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d M Y'))
                                ->removeField('sampai_tanggal');
                        }
                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('guru')
                    ->label('Filter Pembuat')
                    ->relationship('guru', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengumuman')
            ->emptyStateDescription('Buat pengumuman pertama untuk siswa dan orang tua.')
            ->emptyStateIcon('heroicon-o-megaphone');
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
            'index' => Pages\ListPengumumen::route('/'),
            'create' => Pages\CreatePengumuman::route('/create'),
            'edit' => Pages\EditPengumuman::route('/{record}/edit'),

        ];
    }
}
