<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequerimientoInventarioResource\Pages;
use App\Models\RequerimientoInventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RequerimientoInventarioResource extends Resource
{
    protected static ?string $model = RequerimientoInventario::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $pluralLabel = 'Requerimientos Inventario';
    protected static ?string $label = 'Req. Inventario';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('nombre')
                    ->required()
                    ->options([
                        'Si' => 'Sí',
                        'No' => 'No',
                    ])
                    ->label('Requiere Inventario')
                    ->native(false),
                
                Forms\Components\Textarea::make('descripcion')
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->label('Descripción'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Requiere Inventario')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Si' => 'success',
                        'No' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
            'index' => Pages\ListRequerimientoInventarios::route('/'),
            'create' => Pages\CreateRequerimientoInventario::route('/create'),
            'edit' => Pages\EditRequerimientoInventario::route('/{record}/edit'),
        ];
    }
}