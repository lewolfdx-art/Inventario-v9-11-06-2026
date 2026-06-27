<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Models\Producto;
use App\Models\Movimiento;
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
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\HtmlString;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;
    protected static ?string $navigationIcon = 'heroicon-s-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Catalogo de Herramientas';
    protected static ?string $pluralLabel = 'Herramientas';
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
                        
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock Actual')
                            ->numeric()
                            ->default(0)
                            ->helperText('Cantidad disponible en inventario'),
                    ])->columns(2),
                
                // ✅ SECCIÓN DE IMAGEN (como en GrupoCategoria)
                Forms\Components\Section::make('Imagen del Producto')
                    ->schema([
                        Forms\Components\FileUpload::make('imagen')
                            ->label('Imagen del producto')
                            ->image()
                            ->directory('productos')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->helperText('Imagen del producto (recomendado: 500x500px)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => !$record?->imagen),
                
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
                            ->reactive()
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
                
                Forms\Components\TextInput::make('serie')
                    ->label('Número de Serie')
                    ->maxLength(100)
                    ->placeholder('Ej: SN-123456789')
                    ->helperText('Ingrese el número de serie del producto')
                    ->visible(fn($get) => $get('req_serie_id') == 2)
                    ->required(fn($get) => $get('req_serie_id') == 2),
                
                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('descripcion')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Descripción'),
                    ]),

                Forms\Components\Section::make('Historial de Recalibraciones')
                    ->description('Registra cada recalibración realizada al producto. El sistema mostrará alertas cuando se acerque la próxima fecha.')
                    ->schema([
                        Forms\Components\Repeater::make('recalibraciones')
                            ->relationship()
                            ->label('Recalibraciones realizadas')
                            ->schema([
                                Forms\Components\DatePicker::make('fecha_recalibracion')
                                    ->label('Fecha de recalibración')
                                    ->required(false)
                                    ->maxDate(now())
                                    ->native(false)
                                    ->placeholder('Seleccione la fecha'),

                                Forms\Components\DatePicker::make('proxima_recalibracion')
                                    ->label('Próxima recalibración')
                                    ->required(false)
                                    ->minDate(now())
                                    ->native(false)
                                    ->helperText('El sistema mostrará alertas cuando se acerque esta fecha'),

                                Forms\Components\Textarea::make('observaciones')
                                    ->label('Observaciones / Certificado / Proveedor')
                                    ->rows(3)
                                    ->placeholder('Ej: Certificado N° 00123, Laboratorio Acreditado'),

                                Forms\Components\TextInput::make('realizada_por_nombre')
                                    ->label('Realizada por')
                                    ->placeholder('Ej: Andres Reyes - Técnico')
                                    ->maxLength(100)
                                    ->required(false),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->reorderable(false)
                            ->deletable(true)
                            ->deleteAction(
                                fn($action) => $action
                                    ->label('Eliminar esta recalibración')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->size('sm')
                                    ->requiresConfirmation()
                                    ->modalHeading('¿Eliminar esta recalibración?')
                                    ->modalDescription('Esta acción no se puede deshacer.')
                                    ->modalSubmitActionLabel('Sí, eliminar')
                                    ->modalCancelActionLabel('Cancelar')
                            )
                            ->addActionLabel('➕ Agregar nueva recalibración')
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(10),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => !$record?->recalibraciones()->exists()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100, 250, 500])
            ->paginated(true)
            ->selectable(true)
            ->searchable(true)
            
            // CABECERA
            ->headerActions([
                Action::make('escanear')
                    ->label('📷 Escanear')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->modalHeading('Escáner de código de barras')
                    ->modalDescription('Escanea el código para registrar SALIDA o ENTRADA automáticamente')
                    ->form([
                        Forms\Components\TextInput::make('scanner_code')
                            ->label('Código de barras')
                            ->placeholder('Escanea el código...')
                            ->autofocus()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $livewire, $set) {
                                if (!empty($state)) {
                                    $livewire->scanner_code = $state;
                                    $livewire->updatedScannerCode($state);
                                    $set('scanner_code', '');
                                    $livewire->dispatch('close-modal');
                                }
                            }),
                    ])
                    ->action(function () {
                        // No hacer nada aquí
                    }),
                
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
                            Excel::import($import, $archivo->getRealPath());
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
                                'serie' => 'Serie',
                                'stock' => 'Stock',
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
                                'created_at' => 'Fecha Registro',
                            ])
                            ->default(['sku', 'modelo', 'nombre', 'categoria', 'subcategoria', 'marca', 'estado', 'stock'])
                            ->columns(2)
                            ->label('Seleccione las columnas'),
                    ])
                    ->action(function (array $data) {
                        $records = Producto::with(['categoria', 'subcategoria', 'marca', 'unidadCompra', 'naturaleza', 'estado', 'reqInventario', 'reqSerie', 'reqLote', 'reqCalibracion'])->get();
                        $columnasSeleccionadas = $data['columnas'] ?? [];
                        $export = new ProductosExport($records, $columnasSeleccionadas);
                        return Excel::download($export, 'productos_todos_' . now()->format('Ymd_His') . '.xlsx');
                    }),
                
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
                                'serie' => 'Serie',
                                'stock' => 'Stock',
                                'categoria' => 'Categoría',
                                'marca' => 'Marca',
                                'estado' => 'Estado',
                                'unidad_compra' => 'Unidad',
                                'naturaleza' => 'Naturaleza',
                            ])
                            ->default(['sku', 'modelo', 'nombre', 'categoria', 'marca', 'estado', 'stock'])
                            ->columns(2)
                            ->label('Seleccione las columnas'),
                    ])
                    ->action(function (array $data) {
                        $records = Producto::with(['categoria', 'marca', 'unidadCompra', 'naturaleza', 'estado'])->get();
                        $columnasSeleccionadas = $data['columnas'] ?? [];
                        $export = new ProductosPdfExport($records, $columnasSeleccionadas);
                        return response()->streamDownload(function () use ($export) {
                            echo $export->getContent();
                        }, 'catalogo_productos_' . now()->format('Ymd_His') . '.pdf', [
                            'Content-Type' => 'application/pdf',
                        ]);
                    }),
            ])
            
            // COLUMNAS
            ->columns([
                // ✅ COLUMNA DE IMAGEN (como en GrupoCategoria)
                ImageColumn::make('imagen')
                    ->label('Imagen')
                    ->getStateUsing(fn ($record) => $record->imagen ? asset('storage/' . $record->imagen) : null)
                    ->size(60)
                    ->circular()
                    ->action(
                        Tables\Actions\Action::make('verImagen')
                            ->label('')
                            ->icon('heroicon-m-eye')
                            ->tooltip('Ver imagen ampliada')
                            ->modalHeading(fn ($record) => "Imagen de: {$record->nombre}")
                            ->modalContent(fn ($record) => self::getImageModalContent($record))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                            ->modalWidth('4xl')
                    )
                    ->extraAttributes([
                        'class' => 'cursor-pointer hover:shadow-lg transition-shadow rounded-full',
                        'title' => 'Haz clic para ampliar'
                    ]),
                
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
                
                Tables\Columns\TextColumn::make('serie')
                    ->label('Serie')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state <= 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')),
                
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
                
                Tables\Columns\TextColumn::make('proxima_recalibracion_formatted')
                    ->label('Próxima recalibración')
                    ->sortable(false)
                    ->badge()
                    ->color(fn($record) => $record->proxima_recalibracion_color)
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('subcategoria_id')
                    ->label('Subcategoría')
                    ->relationship('subcategoria', 'nombre')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('marca_id')
                    ->label('Marca')
                    ->relationship('marca', 'nombre')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('unidad_compra_id')
                    ->label('Unidad de Compra')
                    ->relationship('unidadCompra', 'nombre')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('naturaleza_id')
                    ->label('Naturaleza')
                    ->relationship('naturaleza', 'nombre')
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('estado_id')
                    ->label('Estado')
                    ->relationship('estado', 'nombre')
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('req_inventario_id')
                    ->label('Requiere Inventario')
                    ->options([
                        '2' => 'Sí',
                        '3' => 'No',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('req_serie_id')
                    ->label('Requiere Serie')
                    ->options([
                        '2' => 'Sí',
                        '3' => 'No',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('req_lote_id')
                    ->label('Requiere Lote')
                    ->options([
                        '2' => 'Sí',
                        '3' => 'No',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('req_calibracion_id')
                    ->label('Requiere Calibración')
                    ->options([
                        '1' => 'Sí',
                        '2' => 'No',
                    ])
                    ->multiple(),
                
                Tables\Filters\Filter::make('created_at')
                    ->label('Fecha de creación')
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
                            ->when($data['created_from'], fn($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn($q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    })
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            
            // ACCIONES POR FILA
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                // 📥 ENTRADA
                Tables\Actions\Action::make('entrada')
                    ->label('📥 Entrada')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->action(function ($record) {
                        $stockAnterior = $record->stock ?? 0;
                        $nuevoStock = $stockAnterior + 1;
                        
                        $record->stock = $nuevoStock;
                        $record->save();
                        
                        Movimiento::create([
                            'producto_id' => $record->id,
                            'tipo' => 'entrada',
                            'cantidad' => 1,
                            'stock_anterior' => $stockAnterior,
                            'stock_nuevo' => $nuevoStock,
                        ]);
                        
                        Notification::make()
                            ->title('📥 Entrada registrada')
                            ->body($record->nombre . ': ' . $stockAnterior . ' → ' . $nuevoStock)
                            ->success()
                            ->send();
                    }),
                
                // 📤 SALIDA
                Tables\Actions\Action::make('salida')
                    ->label('📤 Salida')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('danger')
                    ->action(function ($record) {
                        $stockAnterior = $record->stock ?? 0;
                        
                        if ($stockAnterior <= 0) {
                            Notification::make()
                                ->title('❌ Sin stock disponible')
                                ->body('No hay stock para dar salida a ' . $record->nombre)
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $nuevoStock = $stockAnterior - 1;
                        
                        $record->stock = $nuevoStock;
                        $record->save();
                        
                        Movimiento::create([
                            'producto_id' => $record->id,
                            'tipo' => 'salida',
                            'cantidad' => 1,
                            'stock_anterior' => $stockAnterior,
                            'stock_nuevo' => $nuevoStock,
                        ]);
                        
                        Notification::make()
                            ->title('📤 Salida registrada')
                            ->body($record->nombre . ': ' . $stockAnterior . ' → ' . $nuevoStock)
                            ->success()
                            ->send();
                    }),
                
                // Imprimir Etiqueta
                Tables\Actions\Action::make('imprimir_etiqueta')
                    ->label('Imprimir Etiqueta')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Producto $record) => $record->sku ? route('etiqueta.producto', $record) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Producto $record): bool => filled($record->sku)),
                
                // Descargar ZPL
                Tables\Actions\Action::make('descargar_zpl')
                    ->label('Descargar ZPL')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->url(fn (Producto $record) => $record->sku ? route('etiqueta-zpl.producto', $record) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Producto $record): bool => filled($record->sku)),
            ])
            
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash')
                        ->color('secondary'),
                    
                    Tables\Actions\BulkAction::make('exportar_seleccionados_excel')
                        ->label('Exportar seleccionados a Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->form([
                            Forms\Components\CheckboxList::make('columnas')
                                ->label('Columnas a exportar')
                                ->options([
                                    'sku' => 'SKU',
                                    'modelo' => 'Modelo',
                                    'nombre' => 'Nombre',
                                    'serie' => 'Serie',
                                    'stock' => 'Stock',
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
                                    'created_at' => 'Fecha Registro',
                                ])
                                ->default(['sku', 'modelo', 'nombre', 'categoria', 'subcategoria', 'marca', 'estado', 'stock'])
                                ->columns(2)
                                ->label('Seleccione las columnas'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->load(['categoria', 'subcategoria', 'marca', 'unidadCompra', 'naturaleza', 'estado', 'reqInventario', 'reqSerie', 'reqLote', 'reqCalibracion']);
                            $export = new ProductosExport($records, $data['columnas'] ?? []);
                            return Excel::download($export, 'Detalle_inventario_herramientas_' . now()->format('Ymd_His') . '.xlsx');
                        }),
                    
                    Tables\Actions\BulkAction::make('exportar_seleccionados_pdf')
                        ->label('Exportar seleccionados a PDF')
                        ->icon('heroicon-o-document')
                        ->color('danger')
                        ->form([
                            Forms\Components\CheckboxList::make('columnas')
                                ->label('Columnas a exportar')
                                ->options([
                                    'sku' => 'SKU',
                                    'modelo' => 'Modelo',
                                    'nombre' => 'Nombre',
                                    'serie' => 'Serie',
                                    'stock' => 'Stock',
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
                                    'created_at' => 'Fecha Registro',
                                ])
                                ->default(['sku', 'modelo', 'nombre', 'categoria', 'marca', 'estado', 'stock'])
                                ->columns(2)
                                ->label('Seleccione las columnas'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->load(['categoria', 'subcategoria', 'marca', 'unidadCompra', 'naturaleza', 'estado', 'reqInventario', 'reqSerie', 'reqLote', 'reqCalibracion']);
                            $columnasSeleccionadas = $data['columnas'] ?? [];
                            $export = new ProductosPdfExport($records, $columnasSeleccionadas);
                            return response()->streamDownload(function () use ($export) {
                                echo $export->getContent();
                            }, 'Detalle_inventario_herramientas_' . now()->format('Ymd_His') . '.pdf', [
                                'Content-Type' => 'application/pdf',
                            ]);
                        }),
                ]),
            ])
            
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

    /**
     * Genera el contenido HTML para el modal de imagen
     */
    protected static function getImageModalContent($record): HtmlString
    {
        if (!$record->imagen) {
            return new HtmlString('<div class="text-center text-gray-500 p-8">No hay imagen disponible</div>');
        }

        $imageUrl = asset('storage/' . $record->imagen);
        $nombre = e($record->nombre);

        return new HtmlString(<<<HTML
            <div class="flex justify-center items-center p-4">
                <img 
                    src="{$imageUrl}" 
                    alt="{$nombre}"
                    class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-xl"
                    style="border: 2px solid #e5e7eb;"
                />
            </div>
        HTML);
    }
}   