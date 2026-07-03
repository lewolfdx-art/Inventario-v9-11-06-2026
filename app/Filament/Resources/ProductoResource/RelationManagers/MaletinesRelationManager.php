<?php

namespace App\Filament\Resources\ProductoResource\RelationManagers;

use App\Models\Maletin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaletinesRelationManager extends RelationManager
{
    protected static string $relationship = 'maletines';

    protected static ?string $recordTitleAttribute = 'nombre';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informacion del Maletin')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre del Maletin')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ingrese el nombre del maletin'),
                        
                        Forms\Components\Select::make('estado')
                            ->label('Estado del Maletin')
                            ->options([
                                'activo' => 'Activo',
                                'prestado' => 'Prestado',
                                'devuelto' => 'Devuelto',
                                'mantenimiento' => 'En Mantenimiento',
                            ])
                            ->default('activo')
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Componentes del Equipo')
                    ->schema([
                        Forms\Components\Repeater::make('componentesEquipo')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('item_numero')
                                    ->label('Item')
                                    ->numeric()
                                    ->default(1)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0)
                                    ->required()
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Contenido')
                                    ->required(false)
                                    ->maxLength(500)
                                    ->placeholder('Ej: EQUIPO DE PRUEBAS: OMICRON CMC356')
                                    ->columnSpan(2),
                                
                                Forms\Components\Toggle::make('incluido')
                                    ->label('Incluido')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->columnSpanFull()
                            ->reorderable(false)
                            ->deletable(true)
                            ->addActionLabel('Agregar Componente')
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(50),
                    ]),
                
                Forms\Components\Section::make('Contenido de la Maleta')
                    ->schema([
                        Forms\Components\Repeater::make('accesoriosSet')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('item_numero')
                                    ->label('Item')
                                    ->numeric()
                                    ->default(1)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0)
                                    ->required()
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Contenido')
                                    ->required(false)
                                    ->maxLength(500)
                                    ->placeholder('Descripcion del accesorio...')
                                    ->columnSpan(2),
                                
                                Forms\Components\Toggle::make('incluido')
                                    ->label('Incluido')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->columnSpanFull()
                            ->reorderable(false)
                            ->deletable(true)
                            ->addActionLabel('Agregar Accesorio')
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(50),
                    ]),
                
                Forms\Components\Section::make('Paquete Adicional de Accesorios')
                    ->schema([
                        Forms\Components\Repeater::make('accesoriosAdicionales')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('item_numero')
                                    ->label('Item')
                                    ->numeric()
                                    ->default(1)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0)
                                    ->required()
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Contenido')
                                    ->required(false)
                                    ->maxLength(500)
                                    ->placeholder('Descripcion del accesorio adicional...')
                                    ->columnSpan(2),
                                
                                Forms\Components\Toggle::make('incluido')
                                    ->label('Incluido')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->columnSpanFull()
                            ->reorderable(false)
                            ->deletable(true)
                            ->addActionLabel('Agregar Accesorio Adicional')
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(50),
                    ]),
                
                Forms\Components\Section::make('Observaciones')
                    ->schema([
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones Generales')
                            ->rows(4)
                            ->maxLength(65535)
                            ->placeholder('Ingrese observaciones adicionales...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre del Maletin')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('componentes_contador')
                    ->label('Componentes')
                    ->getStateUsing(function ($record) {
                        $count = $record->componentesEquipo->where('incluido', true)->count();
                        return $count . ' elementos';
                    })
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('accesorios_contador')
                    ->label('Accesorios')
                    ->getStateUsing(function ($record) {
                        $count = $record->accesoriosSet->where('incluido', true)->count();
                        return $count . ' elementos';
                    })
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('adicionales_contador')
                    ->label('Adicionales')
                    ->getStateUsing(function ($record) {
                        $count = $record->accesoriosAdicionales->where('incluido', true)->count();
                        return $count . ' elementos';
                    })
                    ->badge()
                    ->color('warning'),
                
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'prestado' => 'warning',
                        'devuelto' => 'info',
                        'mantenimiento' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'activo' => 'Activo',
                        'prestado' => 'Prestado',
                        'devuelto' => 'Devuelto',
                        'mantenimiento' => 'En Mantenimiento',
                        default => (string) $state,
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'prestado' => 'Prestado',
                        'devuelto' => 'Devuelto',
                        'mantenimiento' => 'En Mantenimiento',
                    ]),
            ])
            ->headerActions([
                // ✅ Asociar maletin existente
                Tables\Actions\AttachAction::make()
                    ->label('Asociar Maletin Existente')
                    ->icon('heroicon-o-link')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('estado', 'activo'))
                    ->recordSelectSearchColumns(['nombre']),
                
                // ✅ Crear nuevo maletin
                Tables\Actions\CreateAction::make()
                    ->label('Crear Nuevo Maletin')
                    ->icon('heroicon-o-plus')
                    ->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('primary'),
                
                Tables\Actions\DetachAction::make()
                    ->label('Desvincular')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger'),
                
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Desvincular seleccionados'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->emptyStateHeading('No hay maletines asociados')
            ->emptyStateDescription('Asocia un maletin existente o crea uno nuevo.')
            ->emptyStateIcon('heroicon-o-briefcase');
    }
}