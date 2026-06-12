<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequerimientoCalibracionResource\Pages;
use App\Models\RequerimientoCalibracion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RequerimientoCalibracionResource extends Resource
{
    protected static ?string $model = RequerimientoCalibracion::class;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $pluralLabel = 'Requerimientos Calibración';
    protected static ?string $label = 'Req. Calibración';

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
                    ->label('Requiere Calibración')
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
                    ->label('Requiere Calibración')
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
            'index' => Pages\ListRequerimientoCalibraciones::route('/'),
            'create' => Pages\CreateRequerimientoCalibracion::route('/create'),
            'edit' => Pages\EditRequerimientoCalibracion::route('/{record}/edit'),
        ];
    }
}