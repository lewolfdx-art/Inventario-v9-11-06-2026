<?php
// app/Filament/Resources/MaletinResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\MaletinResource\Pages;
use App\Models\Maletin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;

class MaletinResource extends Resource
{
    protected static ?string $model = Maletin::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Gestion de Equipos';
    protected static ?string $pluralLabel = 'Maletines';
    protected static ?string $label = 'Maletin';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ==================== SECCION 1: INFORMACION GENERAL ====================
                Forms\Components\Section::make('INFORMACION GENERAL')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del Maletin')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ingrese el nombre del maletin')
                        ->columnSpan(1),
                    
                    Forms\Components\Placeholder::make('fecha_creacion')
                        ->label('Fecha de Creacion')
                        ->content(now()->format('d/m/Y H:i'))
                        ->helperText('La fecha se genera automaticamente')
                        ->columnSpan(1),
                    
                    Forms\Components\Select::make('estado')
                        ->label('Estado del Maletin')
                        ->options([
                            'activo' => 'Activo',
                            'prestado' => 'Prestado',
                            'devuelto' => 'Devuelto',
                            'mantenimiento' => 'En Mantenimiento',
                        ])
                        ->default('activo')
                        ->required()
                        ->columnSpan(2),
                    
                    Forms\Components\Select::make('producto_id')
                        ->label('Asociar a Producto (Manual)')
                        ->relationship('productos', 'nombre')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->placeholder('Seleccione uno o más productos...')
                        ->helperText('Seleccione el/los producto(s) que usarán este maletín')
                        ->columnSpan(2),
                ])->columns(2),
                
                // ==================== SECCION 2: COMPONENTES DEL EQUIPO ====================
                Forms\Components\Section::make('COMPONENTES DEL EQUIPO')
                    ->description('Componentes principales que componen el equipo')
                    ->schema([
                        Forms\Components\Repeater::make('componentesEquipo')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('item_numero')
                                    ->label('Item')
                                    ->numeric()
                                    ->default(fn($livewire) => self::getNextItemNumber($livewire, 'componentes'))
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
                                    ->placeholder('Ej: EQUIPO DE PRUEBAS: OMICRON CMC356 S/N ML142W')
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
                            ->defaultItems(2)
                            ->minItems(0)
                            ->maxItems(50)
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['item_numero'] = $data['item_numero'] ?? 0;
                                return $data;
                            }),
                    ]),
                
                // ==================== SECCION 3: CONTENIDO DE LA MALETA ====================
                Forms\Components\Section::make('CONTENIDO DE LA MALETA')
                    ->description('Lista de accesorios que componen el maletin')
                    ->schema([
                        Forms\Components\Repeater::make('accesoriosSet')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('item_numero')
                                    ->label('Item')
                                    ->numeric()
                                    ->default(fn($livewire) => self::getNextItemNumber($livewire, 'set'))
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
                            ->defaultItems(14)
                            ->minItems(0)
                            ->maxItems(50)
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['item_numero'] = $data['item_numero'] ?? 0;
                                return $data;
                            }),
                    ]),
                
                // ==================== SECCION 4: PAQUETE ADICIONAL DE ACCESORIOS ====================
                Forms\Components\Section::make('PAQUETE ADICIONAL DE ACCESORIOS')
                    ->description('Accesorios adicionales')
                    ->schema([
                        Forms\Components\Repeater::make('accesoriosAdicionales')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('item_numero')
                                    ->label('Item')
                                    ->numeric()
                                    ->default(fn($livewire) => self::getNextItemNumber($livewire, 'adicional'))
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
                
                // ==================== SECCION 5: OBSERVACIONES ====================
                Forms\Components\Section::make('OBSERVACIONES')
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100])
            ->paginated(true)
            ->selectable(true)
            ->searchable()
            
            ->headerActions([
                // Botón de importación desde Excel
                Tables\Actions\Action::make('importar_excel')
                    ->label('Importar desde Excel')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->modalHeading('Importar Maletín desde Excel')
                    ->modalDescription('Selecciona el archivo Excel con el formato de checklist de entrega')
                    ->form([
                        Forms\Components\FileUpload::make('archivo')
                            ->label('Archivo Excel')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel'
                            ])
                            ->maxSize(10240)
                            ->columnSpanFull()
                            ->helperText('Solo archivos .xlsx o .xls')
                            ->storeFiles(false),
                    ])
                    ->action(function (array $data) {
                        try {
                            $archivo = $data['archivo'];
                            
                            if (is_array($archivo)) {
                                $archivo = $archivo[0] ?? null;
                            }
                            
                            if (!$archivo instanceof \Illuminate\Http\UploadedFile) {
                                throw new \Exception('El archivo no es válido.');
                            }
                            
                            if (!$archivo->isValid()) {
                                throw new \Exception('El archivo no es válido o está corrupto.');
                            }
                            
                            $tempPath = $archivo->getRealPath();
                            
                            if (!file_exists($tempPath)) {
                                throw new \Exception('El archivo temporal no existe: ' . $tempPath);
                            }
                            
                            // 👇 CREAR IMPORTADOR CON cleanExisting = true
                            $import = new \App\Imports\MaletinImport(
                                $data['categoria_id'] ?? null,
                                $data['subcategoria_id'] ?? null,
                                true // 👈 Esto limpia los datos existentes
                            );
                            
                            $import->import($tempPath);
                            
                            Notification::make()
                                ->title('✅ Importación exitosa')
                                ->body('El maletín ha sido importado correctamente')
                                ->success()
                                ->send();
                            
                            return redirect()->back();
                            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Error al importar')
                                ->body('Ocurrió un error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                            
                            Log::error('Error al importar Excel: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                        }
                    })
                    ->modalSubmitActionLabel('Importar')
                    ->modalCancelActionLabel('Cancelar'),
            ])
            
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre del Maletin')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('productos_asociados')
                    ->label('Producto Asociado')
                    ->getStateUsing(function ($record) {
                        if ($record->productos->isNotEmpty()) {
                            return '✅ ' . $record->productos->pluck('nombre')->implode(', ');
                        }
                        return '❌ Sin asociar';
                    })
                    ->badge()
                    ->color(fn ($state) => str_contains($state, '✅') ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->url(fn ($record) => $record->productos->isNotEmpty() ? route('filament.admin.resources.productos.edit', $record->productos->first()) : null)
                    ->openUrlInNewTab(false)
                    ->tooltip('Haz clic para ir al producto asociado'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Creacion')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('componentes_contador')
                    ->label('Componentes')
                    ->getStateUsing(function ($record) {
                        $count = $record->componentesEquipo->where('incluido', true)->count();
                        return $count . ' elemento(s)';
                    })
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('componentes_resumen')
                    ->label('Resumen Componentes')
                    ->getStateUsing(function ($record) {
                        $items = $record->componentesEquipo
                            ->where('incluido', true)
                            ->take(3)
                            ->map(function ($item) {
                                return $item->descripcion;
                            })->implode(' | ');
                        $total = $record->componentesEquipo->where('incluido', true)->count();
                        return $total > 3 ? $items . ' ...' : ($items ?: 'Sin componentes');
                    })
                    ->limit(80)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('accesorios_contador')
                    ->label('Accesorios')
                    ->getStateUsing(function ($record) {
                        $count = $record->accesoriosSet->where('incluido', true)->count();
                        return $count . ' elemento(s)';
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('accesorios_resumen')
                    ->label('Resumen Accesorios')
                    ->getStateUsing(function ($record) {
                        $items = $record->accesoriosSet
                            ->where('incluido', true)
                            ->take(3)
                            ->map(function ($item) {
                                return $item->descripcion;
                            })->implode(' | ');
                        $total = $record->accesoriosSet->where('incluido', true)->count();
                        return $total > 3 ? $items . ' ...' : ($items ?: 'Sin accesorios');
                    })
                    ->limit(80)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('adicionales_contador')
                    ->label('Adicionales')
                    ->getStateUsing(function ($record) {
                        $count = $record->accesoriosAdicionales->where('incluido', true)->count();
                        return $count . ' elemento(s)';
                    })
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('adicionales_resumen')
                    ->label('Resumen Adicionales')
                    ->getStateUsing(function ($record) {
                        $items = $record->accesoriosAdicionales
                            ->where('incluido', true)
                            ->take(3)
                            ->map(function ($item) {
                                return $item->descripcion;
                            })->implode(' | ');
                        $total = $record->accesoriosAdicionales->where('incluido', true)->count();
                        return $total > 3 ? $items . ' ...' : ($items ?: 'Sin adicionales');
                    })
                    ->limit(80)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                
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
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
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
                
                Tables\Filters\Filter::make('created_at')
                    ->label('Fecha de Creacion')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn($q, $value) => $q->whereDate('created_at', '>=', $value)
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn($q, $value) => $q->whereDate('created_at', '<=', $value)
                            );
                    })
                    ->columns(2),
            ])
            
            // ========== ACCIONES ==========
            ->actions([
                // ✅ BOTÓN PARA CONVERTIR EN PRODUCTO (PRIMERO)
                Tables\Actions\Action::make('convertir_producto')
                    ->label(fn ($record) => $record->productos()->exists() ? '✅ Producto creado' : '📦 Crear Producto')
                    ->icon(fn ($record) => $record->productos()->exists() ? 'heroicon-o-check-circle' : 'heroicon-o-plus-circle')
                    ->color(fn ($record) => $record->productos()->exists() ? 'success' : 'primary')
                    ->requiresConfirmation(fn ($record) => !$record->productos()->exists())
                    ->modalHeading(fn ($record) => $record->productos()->exists() ? 'Producto ya creado' : 'Crear Producto desde Maletín')
                    ->modalDescription(fn ($record) => $record->productos()->exists() 
                        ? 'Este maletín ya tiene un producto asociado: ' . $record->productos->first()->nombre 
                        : '¿Estás seguro de que deseas crear un producto con este maletín? Se creará con stock 1.'
                    )
                    ->modalSubmitActionLabel('Sí, crear producto')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function ($record) {
                        if ($record->productos()->exists()) {
                            Notification::make()
                                ->title('ℹ️ Este maletín ya tiene un producto asociado')
                                ->body('Producto: ' . $record->productos->first()->nombre)
                                ->info()
                                ->send();
                            return;
                        }
                        
                        // ✅ Obtener categoría "Maleta" y subcategoría "Maletín"
                        $categoria = \App\Models\Categoria::where('nombre', 'Maleta')->first();
                        $subcategoria = \App\Models\Subcategoria::where('nombre', 'Maletín')->first();
                        
                        // ✅ Crear el producto con categoría y subcategoría
                        $producto = \App\Models\Producto::create([
                            'sku' => 'MAL-' . strtoupper(uniqid()),
                            'modelo' => $record->nombre,
                            'nombre' => $record->nombre,
                            'stock' => 1,
                            'estado_id' => 1,
                            'categoria_id' => $categoria?->id ?? null,
                            'subcategoria_id' => $subcategoria?->id ?? null,
                            'marca_id' => null,
                            'unidad_compra_id' => null,
                            'naturaleza_id' => null,
                            'req_inventario_id' => null,
                            'req_serie_id' => null,
                            'req_lote_id' => null,
                            'req_calibracion_id' => null,
                            'serie' => null,
                            'imagen' => null,
                            'descripcion' => 'Producto creado desde maletín: ' . $record->nombre,
                        ]);
                        
                        $record->productos()->attach($producto->id);
                        
                        Notification::make()
                            ->title('✅ Producto creado con éxito')
                            ->body("Producto '{$producto->nombre}' creado con stock 1, categoría Maleta, subcategoría Maletín y maletín asociado")
                            ->success()
                            ->send();
                    }),
                
                // ✅ BOTÓN VER (SEGUNDO)
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                
                // ✅ BOTÓN EDITAR (TERCERO)
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('primary'),
                
                // ✅ BOTÓN ELIMINAR (CUARTO)
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ])
            
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            
            ->striped()
            ->emptyStateHeading('No hay maletines')
            ->emptyStateDescription('Crea un nuevo maletin para comenzar.')
            ->emptyStateIcon('heroicon-o-briefcase');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaletines::route('/'),
            'create' => Pages\CreateMaletin::route('/create'),
            'edit' => Pages\EditMaletin::route('/{record}/edit'),
            'view' => Pages\ViewMaletin::route('/{record}'),
        ];
    }

    /**
     * Genera el siguiente numero de item automatico
     */
    protected static function getNextItemNumber($livewire, $tipo): int
    {
        if (!$livewire || !$livewire->data) {
            return 1;
        }
        
        $items = [];
        if ($tipo === 'componentes') {
            $items = $livewire->data['componentesEquipo'] ?? [];
        } elseif ($tipo === 'set') {
            $items = $livewire->data['accesoriosSet'] ?? [];
        } else {
            $items = $livewire->data['accesoriosAdicionales'] ?? [];
        }
        
        if (empty($items)) {
            return 1;
        }
        
        $max = 0;
        foreach ($items as $item) {
            if (isset($item['item_numero']) && (int) $item['item_numero'] > $max) {
                $max = (int) $item['item_numero'];
            }
        }
        
        return $max + 1;
    }
}