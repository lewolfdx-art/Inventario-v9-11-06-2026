<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Models\Producto;
use App\Models\Movimiento;
use App\Models\MovimientoFoto;
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
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\MaletinResource;

Log::info('=== ProductoResource cargado ===');

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
                
                // ✅ SECCIÓN DE IMAGEN
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

                // ✅ SECCIÓN DE RECALIBRACIONES
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
                                    ->native(false)
                                    ->placeholder('Seleccione la fecha')
                                    ->helperText('Seleccione la fecha de próxima recalibración'),

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
                                    ->modalSubmitActionLabel('Si, eliminar')
                                    ->modalCancelActionLabel('Cancelar')
                            )
                            ->addActionLabel('➕ Agregar nueva recalibración')
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(10),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => !$record?->recalibraciones()->exists()),

                // ==================== ✅ SECCIÓN: HISTORIAL DE CAMBIOS ====================
                Forms\Components\Section::make('📋 Historial de Cambios')
                    ->label('Historial de Cambios')
                    ->schema([
                        Forms\Components\Placeholder::make('historial')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record) {
                                    return 'No hay historial disponible para un producto nuevo.';
                                }
                                
                                $logs = $record->activities()->latest()->get();
                                
                                if ($logs->isEmpty()) {
                                    return 'No hay cambios registrados para este producto.';
                                }
                                
                                $html = '<div class="space-y-4 max-h-[400px] overflow-y-auto p-2">';
                                foreach ($logs as $log) {
                                    $user = $log->causer ? $log->causer->name : 'Sistema';
                                    $date = $log->created_at->format('d/m/Y H:i:s');
                                    $event = $log->event ?? 'cambio';
                                    $description = $log->description ?? 'Sin descripción';
                                    
                                    $badgeColor = match($event) {
                                        'creado' => 'success',
                                        'actualizado' => 'info',
                                        'eliminado' => 'danger',
                                        'restaurado' => 'warning',
                                        default => 'secondary',
                                    };
                                    
                                    $changesHtml = '';
                                    if ($log->properties && isset($log->properties['changes_formatted'])) {
                                        $changes = $log->properties['changes_formatted'];
                                        if (!empty($changes)) {
                                            $changesHtml = '<ul class="text-sm text-gray-600 ml-4 mt-2 list-disc">';
                                            foreach ($changes as $change) {
                                                $changesHtml .= "<li>{$change}</li>";
                                            }
                                            $changesHtml .= '</ul>';
                                        }
                                    }
                                    
                                    $html .= <<<HTML
                                        <div class="border rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow bg-white">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{$badgeColor}-100 text-{$badgeColor}-800">
                                                        {$event}
                                                    </span>
                                                    <span class="text-sm font-medium ml-2">{$description}</span>
                                                </div>
                                                <div class="text-xs text-gray-500 text-right flex-shrink-0 ml-4">
                                                    <div class="font-medium">👤 {$user}</div>
                                                    <div>🕐 {$date}</div>
                                                </div>
                                            </div>
                                            {$changesHtml}
                                        </div>
                                    HTML;
                                }
                                $html .= '</div>';
                                
                                return new HtmlString($html);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => !$record?->activities()->exists()),
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
                
                // ✅ BOTÓN DE HISTORIAL GLOBAL
                Action::make('historial_global')
                    ->label('📋 Historial Global')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Historial de Cambios - Productos')
                    ->modalContent(function () {
                        $logs = \Spatie\Activitylog\Models\Activity::where('log_name', 'producto')
                            ->with('causer')
                            ->latest()
                            ->limit(100)
                            ->get();
                        
                        if ($logs->isEmpty()) {
                            return new HtmlString('<div class="text-center text-gray-500 p-8">No hay registros de actividad.</div>');
                        }
                        
                        $html = '<div class="space-y-3 max-h-[70vh] overflow-y-auto p-2">';
                        foreach ($logs as $log) {
                            $user = $log->causer ? $log->causer->name : 'Sistema';
                            $date = $log->created_at->format('d/m/Y H:i:s');
                            $event = $log->event ?? 'cambio';
                            
                            $badgeColor = match($event) {
                                'creado' => 'success',
                                'actualizado' => 'info',
                                'eliminado' => 'danger',
                                'restaurado' => 'warning',
                                default => 'secondary',
                            };
                            
                            $subject = $log->subject;
                            $subjectInfo = $subject ? "{$subject->sku} - {$subject->nombre}" : 'N/A';
                            
                            $html .= <<<HTML
                                <div class="border-b border-gray-200 pb-3 last:border-0 hover:bg-gray-50 p-2 rounded">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{$badgeColor}-100 text-{$badgeColor}-800">
                                                {$event}
                                            </span>
                                            <span class="text-sm ml-2">{$log->description}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 text-right flex-shrink-0 ml-4">
                                            <div>👤 {$user}</div>
                                            <div>🕐 {$date}</div>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1">
                                        📦 Producto: <strong>{$subjectInfo}</strong>
                                    </div>
                                </div>
                            HTML;
                        }
                        $html .= '</div>';
                        
                        return new HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('4xl'),
            ])
            
            // COLUMNAS - CON TOGGLES EN TODAS
            ->columns([
                // ✅ COLUMNA DE IMAGEN (siempre visible)
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
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),
                
                // ✅ CÓDIGO DE BARRAS
                ImageColumn::make('barcode')
                    ->label('Código Barras')
                    ->getStateUsing(fn (Producto $record) => $record->sku ? route('barcode.producto', $record) : null)
                    ->size(120)
                    ->extraImgAttributes([
                        'alt' => 'Código de barras',
                        'loading' => 'lazy',
                        'style' => 'image-rendering: crisp-edges; background: white; padding: 4px; border-radius: 4px; width: 100%; height: auto; object-fit: contain;',
                    ])
                    ->placeholder('Sin SKU')
                    ->toggleable(isToggledHiddenByDefault: false),
                
                // ✅ SKU
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                // ✅ MODELO
                Tables\Columns\TextColumn::make('modelo')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // ✅ NOMBRE
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                // ✅ SERIE
                Tables\Columns\TextColumn::make('serie')
                    ->label('Serie')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // ✅ STOCK
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state <= 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success'))
                    ->toggleable(isToggledHiddenByDefault: false),
                
                // ✅ MALETÍN ASOCIADO (NUEVO - AGREGADO)
                Tables\Columns\TextColumn::make('maletin_estado')
                ->label('Maletín')
                ->getStateUsing(function ($record) {
                    $maletines = $record->maletines;
                    if ($maletines->isNotEmpty()) {
                        $nombres = $maletines->pluck('nombre')->implode(', ');
                        return '📦 ' . $nombres;
                    }
                    return null;
                })
                ->badge()
                ->color(fn ($state) => $state ? 'success' : null)
                ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : null)
                ->toggleable(isToggledHiddenByDefault: false)
                ->placeholder('')
                ->action(
                    Tables\Actions\Action::make('irMaletin')
                        ->label('')
                        ->icon('heroicon-o-arrow-right')
                        ->tooltip('Editar maletín asociado')
                        ->url(fn ($record) => $record->maletines->isNotEmpty() 
                            ? '/admin/maletins/' . $record->maletines->first()->id . '/edit'
                            : null
                        )
                        ->openUrlInNewTab(false)
                        ->color('primary')
                ),
                
                // ✅ CATEGORÍA
                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // ✅ SUBCATEGORÍA
                Tables\Columns\TextColumn::make('subcategoria.nombre')
                    ->label('Subcategoría')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // ✅ MARCA
                Tables\Columns\TextColumn::make('marca.nombre')
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('secondary')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // ✅ UNIDAD DE COMPRA
                Tables\Columns\TextColumn::make('unidadCompra.nombre')
                    ->label('Unidad')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // ✅ NATURALEZA
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
                
                // ✅ ESTADO
                Tables\Columns\TextColumn::make('estado.nombre')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'activo' => 'success',
                        'inactivo' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                
                // ✅ REQUIERE INVENTARIO
                Tables\Columns\IconColumn::make('reqInventario.nombre')
                    ->label('Inventario')
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
                
                // ✅ REQUIERE SERIE
                Tables\Columns\IconColumn::make('reqSerie.nombre')
                    ->label('Serie Req')
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
                
                // ✅ REQUIERE LOTE
                Tables\Columns\IconColumn::make('reqLote.nombre')
                    ->label('Lote Req')
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
                
                // ✅ REQUIERE CALIBRACIÓN
                Tables\Columns\IconColumn::make('reqCalibracion.nombre')
                    ->label('Calib Req')
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
                
                // ✅ PRÓXIMA RECALIBRACIÓN
                Tables\Columns\TextColumn::make('proxima_recalibracion_formatted')
                    ->label('Próx. Recalibración')
                    ->sortable(false)
                    ->badge()
                    ->color(fn($record) => $record->proxima_recalibracion_color)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // ✅ FECHA CREACIÓN
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
                        '2' => 'Si',
                        '3' => 'No',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('req_serie_id')
                    ->label('Requiere Serie')
                    ->options([
                        '2' => 'Si',
                        '3' => 'No',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('req_lote_id')
                    ->label('Requiere Lote')
                    ->options([
                        '2' => 'Si',
                        '3' => 'No',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('req_calibracion_id')
                    ->label('Requiere Calibración')
                    ->options([
                        '1' => 'Si',
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
            
            // ==================== ✅ ACCIONES POR FILA ====================
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                // ==================== ✅ ACCIONES CON FOTOS ====================
                
                // 📥 FOTOENT - Entrada con Fotos
                Tables\Actions\Action::make('fotoent')
                    ->label('📥 FotoEnt')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('gray')
                    ->modalHeading('Registrar Entrada de Equipo con Fotos')
                    ->modalDescription('Registra la entrada del equipo y adjunta fotos del estado actual')
                    ->form([
                        Forms\Components\TextInput::make('cantidad')
                            ->label('Cantidad')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                        
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Ej: Equipo regresa de reparación, estado...')
                            ->rows(3),
                        
                        Forms\Components\TextInput::make('realizado_por')
                            ->label('Recibido por')
                            ->placeholder('Nombre de la persona que recibe')
                            ->maxLength(100)
                            ->default(Auth::user()?->name ?? 'Sistema'),
                        
                        Forms\Components\FileUpload::make('fotos')
                            ->label('📸 Fotos del equipo (al regresar)')
                            ->multiple()
                            ->image()
                            ->directory('movimientos/entrada')
                            ->visibility('public')
                            ->maxFiles(10)
                            ->imageEditor()
                            ->helperText('Toma fotos del estado del equipo cuando regresa')
                            ->columnSpanFull(),
                    ])
                    ->action(function ($record, array $data, $livewire) {
                        Log::info('========== FOTOENT INICIADO ==========');
                        
                        $stockAnterior = $record->stock ?? 0;
                        $cantidad = $data['cantidad'] ?? 1;
                        $nuevoStock = $stockAnterior + $cantidad;
                        
                        $fotos = $livewire->data['fotos'] ?? $data['fotos'] ?? [];
                        Log::info('Fotos desde Livewire:', ['count' => count($fotos)]);
                        
                        $movimiento = Movimiento::create([
                            'producto_id' => $record->id,
                            'tipo' => 'entrada',
                            'cantidad' => $cantidad,
                            'stock_anterior' => $stockAnterior,
                            'stock_nuevo' => $nuevoStock,
                            'observaciones' => $data['observaciones'] ?? null,
                            'realizado_por' => $data['realizado_por'] ?? Auth::user()?->name ?? 'Sistema',
                        ]);
                        
                        Log::info('Movimiento creado:', ['id' => $movimiento->id]);
                        
                        $fotosGuardadas = 0;
                        
                        if (!empty($fotos) && is_array($fotos)) {
                            Log::info('Procesando ' . count($fotos) . ' foto(s)...');
                            
                            foreach ($fotos as $foto) {
                                try {
                                    if ($foto instanceof TemporaryUploadedFile) {
                                        $path = $foto->store('movimientos/entrada', 'public');
                                        MovimientoFoto::create([
                                            'movimiento_id' => $movimiento->id,
                                            'ruta_imagen' => $path,
                                            'descripcion' => 'Foto de entrada',
                                            'tipo' => 'entrada',
                                        ]);
                                        $fotosGuardadas++;
                                    } elseif (is_string($foto)) {
                                        MovimientoFoto::create([
                                            'movimiento_id' => $movimiento->id,
                                            'ruta_imagen' => $foto,
                                            'descripcion' => 'Foto de entrada',
                                            'tipo' => 'entrada',
                                        ]);
                                        $fotosGuardadas++;
                                    } elseif (is_array($foto) && isset($foto['path'])) {
                                        MovimientoFoto::create([
                                            'movimiento_id' => $movimiento->id,
                                            'ruta_imagen' => $foto['path'],
                                            'descripcion' => 'Foto de entrada',
                                            'tipo' => 'entrada',
                                        ]);
                                        $fotosGuardadas++;
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Error guardando foto de entrada:', ['msg' => $e->getMessage()]);
                                }
                            }
                        }
                        
                        Log::info('Total fotos guardadas en ENTRADA: ' . $fotosGuardadas);
                        
                        $record->stock = $nuevoStock;
                        $record->save();
                        
                        Log::info('========== FOTOENT FINALIZADO ==========');
                        
                        Notification::make()
                            ->title('📥 Entrada registrada con éxito')
                            ->body("{$record->nombre}: {$stockAnterior} → {$nuevoStock} | Fotos: {$fotosGuardadas}")
                            ->success()
                            ->send();
                    }),
                
                // 📤 FOTOSAL - Salida con Fotos
                Tables\Actions\Action::make('fotosal')
                    ->label('📤 FotoSal')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('gray')
                    ->modalHeading('Registrar Salida de Equipo con Fotos')
                    ->modalDescription('Registra la salida del equipo y adjunta fotos del estado actual')
                    ->form([
                        Forms\Components\TextInput::make('cantidad')
                            ->label('Cantidad')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                        
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Ej: Equipo sale para reparación, estado actual...')
                            ->rows(3),
                        
                        Forms\Components\TextInput::make('realizado_por')
                            ->label('Realizado por')
                            ->placeholder('Nombre de la persona que retira')
                            ->maxLength(100)
                            ->default(Auth::user()?->name ?? 'Sistema'),
                        
                        Forms\Components\FileUpload::make('fotos')
                            ->label('📸 Fotos del equipo (al salir)')
                            ->multiple()
                            ->image()
                            ->directory('movimientos/salida')
                            ->visibility('public')
                            ->maxFiles(10)
                            ->imageEditor()
                            ->helperText('Toma fotos del estado actual del equipo antes de que salga')
                            ->columnSpanFull(),
                    ])
                    ->action(function ($record, array $data, $livewire) {
                        Log::info('========== FOTOSAL INICIADO ==========');
                        
                        $stockAnterior = $record->stock ?? 0;
                        $cantidad = $data['cantidad'] ?? 1;
                        
                        if ($stockAnterior < $cantidad) {
                            Notification::make()
                                ->title('❌ Stock insuficiente')
                                ->body("Stock actual: {$stockAnterior}, solicitado: {$cantidad}")
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $nuevoStock = $stockAnterior - $cantidad;
                        
                        $fotos = $livewire->data['fotos'] ?? $data['fotos'] ?? [];
                        Log::info('Fotos desde Livewire:', ['count' => count($fotos)]);
                        
                        $movimiento = Movimiento::create([
                            'producto_id' => $record->id,
                            'tipo' => 'salida',
                            'cantidad' => $cantidad,
                            'stock_anterior' => $stockAnterior,
                            'stock_nuevo' => $nuevoStock,
                            'observaciones' => $data['observaciones'] ?? null,
                            'realizado_por' => $data['realizado_por'] ?? Auth::user()?->name ?? 'Sistema',
                        ]);
                        
                        Log::info('Movimiento creado:', ['id' => $movimiento->id]);
                        
                        $fotosGuardadas = 0;
                        
                        if (!empty($fotos) && is_array($fotos)) {
                            Log::info('Procesando ' . count($fotos) . ' foto(s)...');
                            
                            foreach ($fotos as $foto) {
                                try {
                                    if ($foto instanceof TemporaryUploadedFile) {
                                        $path = $foto->store('movimientos/salida', 'public');
                                        MovimientoFoto::create([
                                            'movimiento_id' => $movimiento->id,
                                            'ruta_imagen' => $path,
                                            'descripcion' => 'Foto de salida',
                                            'tipo' => 'salida',
                                        ]);
                                        $fotosGuardadas++;
                                    } elseif (is_string($foto)) {
                                        MovimientoFoto::create([
                                            'movimiento_id' => $movimiento->id,
                                            'ruta_imagen' => $foto,
                                            'descripcion' => 'Foto de salida',
                                            'tipo' => 'salida',
                                        ]);
                                        $fotosGuardadas++;
                                    } elseif (is_array($foto) && isset($foto['path'])) {
                                        MovimientoFoto::create([
                                            'movimiento_id' => $movimiento->id,
                                            'ruta_imagen' => $foto['path'],
                                            'descripcion' => 'Foto de salida',
                                            'tipo' => 'salida',
                                        ]);
                                        $fotosGuardadas++;
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Error guardando foto de salida:', ['msg' => $e->getMessage()]);
                                }
                            }
                        }
                        
                        Log::info('Total fotos guardadas en SALIDA: ' . $fotosGuardadas);
                        
                        $record->stock = $nuevoStock;
                        $record->save();
                        
                        Log::info('========== FOTOSAL FINALIZADO ==========');
                        
                        Notification::make()
                            ->title('📤 Salida registrada con éxito')
                            ->body("{$record->nombre}: {$stockAnterior} → {$nuevoStock} | Fotos: {$fotosGuardadas}")
                            ->success()
                            ->send();
                    }),
                
                // ==================== ACCIONES EXISTENTES ====================
                
                // 📥 ENTRADA (rápida)
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
                            'observaciones' => 'Entrada rápida',
                            'realizado_por' => Auth::user()?->name ?? 'Sistema',
                        ]);
                        
                        Notification::make()
                            ->title('📥 Entrada registrada')
                            ->body($record->nombre . ': ' . $stockAnterior . ' → ' . $nuevoStock)
                            ->success()
                            ->send();
                    }),
                
                // 📤 SALIDA (rápida)
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
                            'observaciones' => 'Salida rápida',
                            'realizado_por' => Auth::user()?->name ?? 'Sistema',
                        ]);
                        
                        Notification::make()
                            ->title('📤 Salida registrada')
                            ->body($record->nombre . ': ' . $stockAnterior . ' → ' . $nuevoStock)
                            ->success()
                            ->send();
                    }),
                
                // ==================== ✅ SHOW FOTOS CON SCROLL ====================
                Tables\Actions\Action::make('show_fotos')
                ->label('📸 Show Fotos')
                ->icon('heroicon-o-photo')
                ->color('info')
                ->modalHeading(fn ($record) => "Historial de Fotos - {$record->nombre}")
                ->modalContent(function ($record) {
                    // ✅ CARGAR TODOS LOS MOVIMIENTOS
                    $fotosQuery = MovimientoFoto::whereHas('movimiento', function ($query) use ($record) {
                        $query->where('producto_id', $record->id);
                    })
                    ->with('movimiento')
                    ->latest()
                    ->get();
            
                    if ($fotosQuery->isEmpty()) {
                        return new HtmlString('<div class="text-center text-gray-600 dark:text-gray-400 p-8">📷 No hay fotos registradas para este producto.</div>');
                    }
            
                    $movimientosAgrupados = $fotosQuery->groupBy('movimiento_id');
                    $totalMovimientos = $movimientosAgrupados->count();
            
                    // ✅ SCROLL EN EL MODAL
                    $html = '<div class="space-y-6 max-h-[75vh] overflow-y-auto p-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">';
            
                    foreach ($movimientosAgrupados as $movimientoId => $fotosDelMovimiento) {
                        $movimiento = $fotosDelMovimiento->first()->movimiento;
                        
                        $tipoColor = $movimiento->tipo == 'entrada' ? 'green' : 'red';
                        $tipoBg = $movimiento->tipo == 'entrada' ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900';
                        $tipoText = $movimiento->tipo == 'entrada' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300';
                        
                        $icon = $movimiento->tipo == 'entrada' ? '📥' : '📤';
                        $tipoLabel = ucfirst($movimiento->tipo);
                        $realizadoPor = $movimiento->realizado_por ?? 'Sistema';
                        $fecha = $movimiento->created_at->format('d/m/Y H:i:s');
                        $observaciones = $movimiento->observaciones ?? '';
            
                        $html .= <<<HTML
                            <div class="border rounded-xl p-5 bg-white dark:bg-gray-800 shadow-sm">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {$tipoBg} {$tipoText}">
                                            {$icon} {$tipoLabel}
                                        </span>
                                        <span class="text-sm ml-3 font-semibold text-gray-800 dark:text-gray-200">Cantidad: {$movimiento->cantidad}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                            Stock: {$movimiento->stock_anterior} → {$movimiento->stock_nuevo}
                                        </span>
                                    </div>
                                    <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                        <div>{$fecha}</div>
                                        <div>👤 {$realizadoPor}</div>
                                    </div>
                                </div>
            
                                <div class="text-sm text-gray-800 dark:text-white mb-4 font-medium">{$observaciones}</div>
            
                                <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-3">
                        HTML;
            
                        foreach ($fotosDelMovimiento as $foto) {
                            $url = asset('storage/' . $foto->ruta_imagen);
                            $descripcion = $foto->descripcion ?? 'Foto del movimiento';
                            $tipo = $movimiento->tipo;
            
                            $html .= <<<HTML
                                <div class="relative group">
                                    <img src="{$url}" alt="{$descripcion}"
                                         class="w-full aspect-square object-cover rounded-xl border border-gray-200 dark:border-gray-600"
                                         style="max-width: 85px; max-height: 85px;">
                                    <div class="absolute bottom-0 left-0 right-0 text-white text-[10px] text-center py-1 rounded-b-xl"
                                         style="background: {$tipoColor};">
                                        {$tipo}
                                    </div>
                                </div>
                            HTML;
                        }
            
                        $html .= <<<HTML
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-3 text-center">
                                    📸 {$fotosDelMovimiento->count()} foto(s) en este movimiento
                                </div>
                            </div>
                        HTML;
                    }
            
                    $html .= '</div>';
            
                    // ✅ Mostrar total de movimientos
                    $html .= <<<HTML
                        <div class="text-center text-xs text-gray-400 dark:text-gray-500 mt-2">
                            📊 Total: {$totalMovimientos} movimiento(s) registrado(s)
                        </div>
                    HTML;
            
                    // ✅ SOLO SCRIPTS PARA CIERRE DE MODALES (si los hay)
                    $html .= <<<HTML
                        <style>
                            /* Estilos para la barra de scroll */
                            .scrollbar-thin::-webkit-scrollbar {
                                width: 6px;
                            }
                            .scrollbar-thin::-webkit-scrollbar-track {
                                background: transparent;
                            }
                            .scrollbar-thin::-webkit-scrollbar-thumb {
                                background: #cbd5e1;
                                border-radius: 3px;
                            }
                            .dark .scrollbar-thin::-webkit-scrollbar-thumb {
                                background: #4b5563;
                            }
                            .scrollbar-thin::-webkit-scrollbar-thumb:hover {
                                background: #94a3b8;
                            }
                            .dark .scrollbar-thin::-webkit-scrollbar-thumb:hover {
                                background: #6b7280;
                            }
                        </style>
                    HTML;
            
                    return new HtmlString($html);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalWidth('6xl')
                ->modalFooterActions([
                    Tables\Actions\Action::make('descargar_todas')
                        ->label('⬇️ Descargar todas')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($record) {
                            $fotos = MovimientoFoto::whereHas('movimiento', function ($query) use ($record) {
                                $query->where('producto_id', $record->id);
                            })->get();
                            
                            if ($fotos->isEmpty()) {
                                Notification::make()
                                    ->title('❌ No hay fotos para descargar')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $zip = new \ZipArchive();
                            $zipName = 'fotos_' . $record->sku . '_' . now()->format('Ymd_His') . '.zip';
                            $zipPath = storage_path('app/temp/' . $zipName);
                            
                            if (!is_dir(storage_path('app/temp'))) {
                                mkdir(storage_path('app/temp'), 0755, true);
                            }
                            
                            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                                foreach ($fotos as $foto) {
                                    $filePath = storage_path('app/public/' . $foto->ruta_imagen);
                                    if (file_exists($filePath)) {
                                        $zip->addFile($filePath, basename($filePath));
                                    }
                                }
                                $zip->close();
                                
                                return response()->download($zipPath)->deleteFileAfterSend(true);
                            }
                            
                            Notification::make()
                                ->title('❌ Error al crear el ZIP')
                                ->danger()
                                ->send();
                        }),
                ]),
                
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

    /**
     * Renderiza las fotos de un movimiento para el modal
     */
    protected static function renderFotosMovimiento($movimiento): string
    {
        if (!$movimiento->fotos || $movimiento->fotos->isEmpty()) {
            return '<div class="text-xs text-gray-400 mt-1">📷 Sin fotos</div>';
        }
        
        $html = '<div class="flex flex-wrap gap-2 mt-2">';
        foreach ($movimiento->fotos as $foto) {
            $url = asset('storage/' . $foto->ruta_imagen);
            $html .= <<<HTML
                <div class="relative group">
                    <img 
                        src="{$url}" 
                        alt="Foto de {$movimiento->tipo}"
                        class="w-20 h-20 object-cover rounded-lg border border-gray-200 cursor-pointer hover:shadow-lg transition-shadow"
                        onclick="window.open('{$url}', '_blank')"
                    />
                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs text-center py-0.5 rounded-b-lg">
                        {$movimiento->tipo}
                    </div>
                </div>
            HTML;
        }
        $html .= '</div>';
        
        return $html;
    }
}