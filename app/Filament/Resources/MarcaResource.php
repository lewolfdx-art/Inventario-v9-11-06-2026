<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarcaResource\Pages;
use App\Models\Marca;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class MarcaResource extends Resource
{
    protected static ?string $model = Marca::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Inventario--Ing Tito - Ing Edgar';
    protected static ?string $pluralLabel = 'Marcas';
    protected static ?string $label = 'Marca';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Marca')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->label('Nombre de la Marca')
                            ->placeholder('Ej: Nike, Sony, HP')
                            ->helperText('Máximo 10 caracteres'),
                        
                        Forms\Components\Textarea::make('descripcion')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Descripción')
                            ->placeholder('Descripción opcional de la marca'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por nombre (búsqueda rápida)
                Tables\Filters\SelectFilter::make('nombre')
                    ->label('Marca')
                    ->options(fn() => Marca::pluck('nombre', 'nombre')->toArray())
                    ->searchable()
                    ->multiple(),
                
                // Filtro por fecha de creación (rango)
                Filter::make('created_at')
                    ->label('Fecha de creación')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Desde'),
                        DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn($q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    })
                    ->columns(2)
                    ->columnSpanFull(),
                
                // Filtro por fecha de actualización (rango)
                Filter::make('updated_at')
                    ->label('Fecha de actualización')
                    ->form([
                        DatePicker::make('updated_from')
                            ->label('Desde'),
                        DatePicker::make('updated_until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['updated_from'], fn($q) => $q->whereDate('updated_at', '>=', $data['updated_from']))
                            ->when($data['updated_until'], fn($q) => $q->whereDate('updated_at', '<=', $data['updated_until']));
                    })
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarcas::route('/'),
            'create' => Pages\CreateMarca::route('/create'),
            'edit' => Pages\EditMarca::route('/{record}/edit'),
        ];
    }
}