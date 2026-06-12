<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NaturalezaResource\Pages;
use App\Models\Naturaleza;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NaturalezaResource extends Resource
{
    protected static ?string $model = Naturaleza::class;
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $pluralLabel = 'Naturalezas';
    protected static ?string $label = 'Naturaleza';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Naturaleza')
                    ->schema([
                        Forms\Components\Select::make('nombre')
                            ->required()
                            ->options([
                                'tangible' => 'Tangible',
                                'intangible' => 'Intangible',
                            ])
                            ->label('Tipo de Naturaleza')
                            ->placeholder('Seleccione una opción'),
                        
                        Forms\Components\Textarea::make('descripcion')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Descripción'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Naturaleza')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'tangible' => 'success',
                        'intangible' => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListNaturalezas::route('/'),
            'create' => Pages\CreateNaturaleza::route('/create'),
            'edit' => Pages\EditNaturaleza::route('/{record}/edit'),
        ];
    }
}