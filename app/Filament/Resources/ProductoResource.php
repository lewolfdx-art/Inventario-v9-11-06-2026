<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Models\Producto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductosImport;
use App\Exports\ProductosExport;
use App\Exports\ProductosPdfExport;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Catalogo de Productos';
    protected static ?string $pluralLabel = 'Productos';
    protected static ?string $label = 'Producto';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos Principales')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->label('SKU')
                            ->placeholder('Ej: SKU001')
                            ->helperText('Código único del producto'),
                        
                        Forms\Components\TextInput::make('modelo')
                            ->required()
                            ->maxLength(100)
                            ->label('Modelo')
                            ->placeholder('Ej: XT-100'),
                        
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(200)
                            ->label('Nombre')
                            ->placeholder('Ej: Producto'),
                    ])->columns(3),
                
                Forms\Components\Section::make('Catálogos (Seleccionar)')
                    ->schema([
                        Forms\Components\Select::make('categoria_id')
                            ->relationship('categoria', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Categoría'),
                        
                        Forms\Components\Select::make('subcategoria_id')
                            ->relationship('subcategoria', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Subcategoría'),
                        
                        Forms\Components\Select::make('marca_id')
                            ->relationship('marca', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Marca'),
                        
                        Forms\Components\Select::make('unidad_compra_id')
                            ->relationship('unidadCompra', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Unidad de Compra'),
                        
                        Forms\Components\Select::make('naturaleza_id')
                            ->relationship('naturaleza', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Naturaleza'),
                        
                        Forms\Components\Select::make('estado_id')
                            ->relationship('estado', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Estado'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Requerimientos')
                    ->schema([
                        Forms\Components\Select::make('req_inventario_id')
                            ->relationship('reqInventario', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('¿Requiere Inventario?'),
                        
                        Forms\Components\Select::make('req_serie_id')
                            ->relationship('reqSerie', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('¿Requiere Serie?'),
                        
                        Forms\Components\Select::make('req_lote_id')
                            ->relationship('reqLote', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('¿Requiere Lote?'),
                        
                        Forms\Components\Select::make('req_calibracion_id')
                            ->relationship('reqCalibracion', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('¿Requiere Calibración?'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('descripcion')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Descripción'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ✅ PAGINACIÓN
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100, 250, 500])
            ->paginated(true)
            
            // ✅ SELECT/DESELECT ALL
            ->selectable(true)
            
            // ✅ BÚSQUEDA GLOBAL
            ->searchable(true)
            
            // ✅ CABECERA (ACCIONES EN LA PARTE SUPERIOR)
            ->headerActions([
                // 1. IMPORTAR - AZUL
                Action::make('importar')
                    ->label('Importar desde Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('primary')
                    ->modalHeading('Importar Productos')
                    ->modalDescription('Seleccione un archivo Excel/CSV con las siguientes columnas:')
                    ->form([
                        Forms\Components\FileUpload::make('archivo')
                            ->label('Archivo Excel/CSV')
                            ->required()
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/csv'])
                            ->maxSize(10240)
                            ->storeFiles(false),
                    ])
                    ->action(function (array $data) {
                        try {
                            $archivo = $data['archivo'];
                            
                            if (!$archivo) {
                                throw new \Exception('No se ha seleccionado ningún archivo');
                            }
                            
                            $import = new \App\Imports\ProductosImport();
                            \Maatwebsite\Excel\Facades\Excel::import($import, $archivo->getRealPath());
                            
                            Notification::make()
                                ->title('✅ Importación completada')
                                ->body('Se importaron ' . $import->getImportados() . ' productos.')
                                ->success()
                                ->send();
                                
                        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                            $errores = [];
                            foreach ($e->failures() as $failure) {
                                $errores[] = "Fila {$failure->row()}: " . implode(', ', $failure->errors());
                            }
                            
                            Notification::make()
                                ->title('❌ Error de validación')
                                ->body(implode("\n", array_slice($errores, 0, 10)))
                                ->danger()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Error en la importación')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                // 2. EXPORTAR TODO A EXCEL - VERDE
                Action::make('exportar_todo_excel')
                    ->label('Exportar todo a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Forms\Components\CheckboxList::make('columnas')
                            ->label('Columnas a exportar')
                            ->options([
                                'sku' => 'SKU',
                                'modelo' => 'Modelo',
                                'nombre' => 'Nombre',
                                'categoria' => 'Categoría',
                                'subcategoria' => 'Subcategoría',
                                'marca' => 'Marca',
                                'unidad_compra' => 'Unidad de Compra',
                                'naturaleza' => 'Naturaleza',
                                'estado' => 'Estado',
                                'req_inventario' => 'Requiere Inventario',
                                'req_serie' => 'Requiere Serie',
                                'req_lote' => 'Requiere Lote',
                                'req_calibracion' => 'Requiere Calibración',
                                'descripcion' => 'Descripción',
                                'created_at' => 'Fecha Registro',
                            ])
                            ->default(['sku', 'modelo', 'nombre', 'categoria', 'subcategoria', 'marca', 'estado'])
                            ->columns(2)
                            ->label('Seleccione las columnas'),
                    ])
                    ->action(function (array $data) {
                        $records = Producto::with(['categoria', 'subcategoria', 'marca', 'unidadCompra', 'naturaleza', 'estado', 'reqInventario', 'reqSerie', 'reqLote', 'reqCalibracion'])->get();
                        
                        $columnasSeleccionadas = $data['columnas'] ?? [];
                        
                        $export = new ProductosExport($records, $columnasSeleccionadas);
                        
                        return Excel::download($export, 'productos_todos_' . now()->format('Ymd_His') . '.xlsx');
                    }),
                
                // 3. EXPORTAR TODO A PDF - ROJO
                Action::make('exportar_todo_pdf')
                    ->label('Exportar todo a PDF')
                    ->icon('heroicon-o-document')
                    ->color('danger')
                    ->form([
                        Forms\Components\CheckboxList::make('columnas')
                            ->label('Columnas a exportar')
                            ->options([
                                'sku' => 'SKU',
                                'modelo' => 'Modelo',
                                'nombre' => 'Nombre',
                                'categoria' => 'Categoría',
                                'marca' => 'Marca',
                                'estado' => 'Estado',
                                'unidad_compra' => 'Unidad',
                                'naturaleza' => 'Naturaleza',
                            ])
                            ->default(['sku', 'modelo', 'nombre', 'categoria', 'marca', 'estado'])
                            ->columns(2)
                            ->label('Seleccione las columnas'),
                    ])
                    ->action(function (array $data) {
                        $records = Producto::with(['categoria', 'marca', 'unidadCompra', 'naturaleza', 'estado'])->get();
                        $columnasSeleccionadas = $data['columnas'] ?? [];
                        
                        $export = new \App\Exports\ProductosPdfExport($records, $columnasSeleccionadas);
                        
                        return response()->streamDownload(function () use ($export) {
                            echo $export->getContent();
                        }, 'catalogo_productos_' . now()->format('Ymd_His') . '.pdf', [
                            'Content-Type' => 'application/pdf',
                        ]);
                    }),
            ])
            
            // ✅ COLUMNAS
            ->columns([
                //Tables\Columns\CheckboxColumn::make('id')
                  //  ->label('')
                    //->alignCenter(),
                
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('modelo')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('subcategoria.nombre')
                    ->label('Subcategoría')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('marca.nombre')
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('secondary'),
                
                Tables\Columns\TextColumn::make('unidadCompra.nombre')
                    ->label('Unidad')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('naturaleza.nombre')
                    ->label('Naturaleza')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'tangible' => 'success',
                        'intangible' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('estado.nombre')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'activo' => 'success',
                        'inactivo' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('reqInventario.nombre')
                    ->label('Inv')
                    ->icon(fn(string $state): string => match ($state) {
                        'Si' => 'heroicon-o-check-circle',
                        'No' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Si' => 'success',
                        'No' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip('Requiere Inventario')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('reqSerie.nombre')
                    ->label('Serie')
                    ->icon(fn(string $state): string => match ($state) {
                        'Si' => 'heroicon-o-check-circle',
                        'No' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Si' => 'success',
                        'No' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip('Requiere Serie')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('reqLote.nombre')
                    ->label('Lote')
                    ->icon(fn(string $state): string => match ($state) {
                        'Si' => 'heroicon-o-check-circle',
                        'No' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Si' => 'success',
                        'No' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip('Requiere Lote')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('reqCalibracion.nombre')
                    ->label('Cal')
                    ->icon(fn(string $state): string => match ($state) {
                        'Si' => 'heroicon-o-check-circle',
                        'No' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Si' => 'success',
                        'No' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip('Requiere Calibración')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            
            // ✅ FILTROS
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('marca_id')
                    ->label('Marca')
                    ->relationship('marca', 'nombre')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('estado_id')
                    ->label('Estado')
                    ->relationship('estado', 'nombre')
                    ->multiple(),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Creado desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Creado hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn($q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),
            ])
            
            // ✅ ACCIONES POR FILA
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            
            // ✅ ACCIONES MASIVAS (para seleccionar múltiples)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // 1. ELIMINAR - GRIS
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash')
                        ->color('secondary'),
                    
                    // 2. EXPORTAR A EXCEL - VERDE
                    Tables\Actions\BulkAction::make('exportar_seleccionados_excel')
                        ->label('Excel Exportar seleccionados')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->form([
                            Forms\Components\CheckboxList::make('columnas')
                                ->label('Columnas a exportar')
                                ->options([
                                    'sku' => 'SKU',
                                    'modelo' => 'Modelo',
                                    'nombre' => 'Nombre',
                                    'categoria' => 'Categoría',
                                    'subcategoria' => 'Subcategoría',
                                    'marca' => 'Marca',
                                    'unidad_compra' => 'Unidad de Compra',
                                    'naturaleza' => 'Naturaleza',
                                    'estado' => 'Estado',
                                ])
                                ->default(['sku', 'modelo', 'nombre', 'categoria', 'marca', 'estado'])
                                ->columns(2)
                                ->label('Seleccione las columnas'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->load(['categoria', 'subcategoria', 'marca', 'unidadCompra', 'naturaleza', 'estado']);
                            $export = new ProductosExport($records, $data['columnas'] ?? []);
                            return Excel::download($export, 'productos_seleccionados_' . now()->format('Ymd_His') . '.xlsx');
                        }),
                    
                    // 3. EXPORTAR A PDF - ROJO
                    Tables\Actions\BulkAction::make('exportar_seleccionados_pdf')
                        ->label('PDF Exportar seleccionados')
                        ->icon('heroicon-o-document')
                        ->color('danger')
                        ->form([
                            Forms\Components\CheckboxList::make('columnas')
                                ->label('Columnas a exportar')
                                ->options([
                                    'sku' => 'SKU',
                                    'modelo' => 'Modelo',
                                    'nombre' => 'Nombre',
                                    'categoria' => 'Categoría',
                                    'subcategoria' => 'Subcategoría',
                                    'marca' => 'Marca',
                                    'unidad_compra' => 'Unidad de Compra',
                                    'naturaleza' => 'Naturaleza',
                                    'estado' => 'Estado',
                                ])
                                ->default(['sku', 'modelo', 'nombre', 'categoria', 'marca', 'estado'])
                                ->columns(2)
                                ->label('Seleccione las columnas'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->load(['categoria', 'subcategoria', 'marca', 'unidadCompra', 'naturaleza', 'estado']);
                            $columnasSeleccionadas = $data['columnas'] ?? [];
                            
                            $export = new ProductosPdfExport($records, $columnasSeleccionadas);
                            
                            return response()->streamDownload(function () use ($export) {
                                echo $export->getContent();
                            }, 'productos_seleccionados_' . now()->format('Ymd_His') . '.pdf', [
                                'Content-Type' => 'application/pdf',
                            ]);
                        }),
                ]),
            ])
            
            // ✅ ESTILOS
            ->striped()
            ->emptyStateHeading('No hay productos')
            ->emptyStateDescription('Crea un producto o importa desde Excel para comenzar.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'edit' => Pages\EditProducto::route('/{record}/edit'),
        ];
    }
}