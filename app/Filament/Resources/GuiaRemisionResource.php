<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuiaRemisionResource\Pages;
use App\Models\GuiaRemision;
use App\Models\Producto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Exports\GuiaRemisionExport;
use App\Exports\GuiaRemisionPdfExport;
use Maatwebsite\Excel\Facades\Excel;

class GuiaRemisionResource extends Resource
{
    protected static ?string $model = GuiaRemision::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Remisión';
    protected static ?string $pluralLabel = 'Guías de Remisión';
    protected static ?string $label = 'Guía de Remisión';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Guía de Remisión')
                    ->schema([
                        Forms\Components\TextInput::make('numero_guia')
                            ->label('Número de Guía')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->default(GuiaRemision::generarNumeroGuia())
                            ->disabled()
                            ->dehydrated(true)
                            ->prefix('📄'),
                        
                        Forms\Components\Select::make('producto_id')
                            ->label('Nombre del Producto')
                            ->options(Producto::with(['marca'])->get()->mapWithKeys(function ($producto) {
                                $nombre = $producto->nombre;
                                if ($producto->marca) {
                                    $nombre .= ' - ' . $producto->marca->nombre;
                                }
                                $nombre .= ' - ' . $producto->modelo;
                                if ($producto->serie) {
                                    $nombre .= ' - Serie: ' . $producto->serie;
                                }
                                return [$producto->id => $nombre];
                            }))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $producto = Producto::with('marca')->find($state);
                                    if ($producto) {
                                        $set('marca', $producto->marca?->nombre ?? '');
                                        $set('modelo', $producto->modelo ?? '');
                                        $set('serie', $producto->serie ?? '');
                                        
                                        $descripcion = GuiaRemision::generarDescripcionCompleta($producto);
                                        $set('descripcion_completa', $descripcion);
                                    }
                                } else {
                                    $set('marca', '');
                                    $set('modelo', '');
                                    $set('serie', '');
                                    $set('descripcion_completa', '');
                                }
                            }),
                        
                        Forms\Components\TextInput::make('marca')
                            ->label('Marca')
                            ->disabled()
                            ->dehydrated(true)
                            ->prefix('🏷️'),
                        
                        Forms\Components\TextInput::make('modelo')
                            ->label('Modelo')
                            ->disabled()
                            ->dehydrated(true)
                            ->prefix('🔧'),
                        
                        Forms\Components\TextInput::make('serie')
                            ->label('Serie')
                            ->disabled()
                            ->dehydrated(true)
                            ->prefix('🔢')
                            ->helperText('La serie se toma automáticamente del producto seleccionado'),
                        
                        Forms\Components\DatePicker::make('fecha_emision')
                            ->label('Fecha de Emisión')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->prefix('📅'),
                        
                        Forms\Components\Textarea::make('descripcion_completa')
                            ->label('Descripción Completa')
                            ->rows(2)
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpanFull()
                            ->helperText('Se genera automáticamente: Nombre + Marca + Modelo + Serie'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->paginated(true)
            ->searchable(true)
            ->selectable(true)
            
            // CABECERA CON ACCIONES
            ->headerActions([
                // Exportar todo a Excel
                Action::make('exportar_todo_excel')
                    ->label('Exportar todo a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Forms\Components\CheckboxList::make('columnas')
                            ->label('Columnas a exportar')
                            ->options([
                                'numero_guia' => 'N° Guía',
                                'producto_nombre' => 'Producto',
                                'marca' => 'Marca',
                                'modelo' => 'Modelo',
                                'serie' => 'Serie',
                                'descripcion_completa' => 'Descripción',
                                'fecha_emision' => 'Fecha Emisión',
                                'created_at' => 'Fecha Registro',
                            ])
                            ->default(['numero_guia', 'producto_nombre', 'serie', 'fecha_emision'])
                            ->columns(2)
                            ->label('Seleccione las columnas'),
                    ])
                    ->action(function (array $data) {
                        $records = GuiaRemision::with('producto')->get();
                        $columnasSeleccionadas = $data['columnas'] ?? [];
                        $export = new GuiaRemisionExport($records, $columnasSeleccionadas);
                        return Excel::download($export, 'guias_remision_' . now()->format('Ymd_His') . '.xlsx');
                    }),

                // Exportar todo a PDF
                Action::make('exportar_todo_pdf')
                    ->label('Exportar todo a PDF')
                    ->icon('heroicon-o-document')
                    ->color('danger')
                    ->form([
                        Forms\Components\CheckboxList::make('columnas')
                            ->label('Columnas a exportar')
                            ->options([
                                'numero_guia' => 'N° Guía',
                                'producto_nombre' => 'Producto',
                                'marca' => 'Marca',
                                'modelo' => 'Modelo',
                                'serie' => 'Serie',
                                'descripcion_completa' => 'Descripción',
                                'fecha_emision' => 'Fecha Emisión',
                            ])
                            ->default(['numero_guia', 'producto_nombre', 'serie', 'fecha_emision'])
                            ->columns(2)
                            ->label('Seleccione las columnas'),
                    ])
                    ->action(function (array $data) {
                        $records = GuiaRemision::with('producto')->get();
                        $columnasSeleccionadas = $data['columnas'] ?? [];
                        $export = new GuiaRemisionPdfExport($records, $columnasSeleccionadas);
                        return $export->download();
                    }),
            ])
            
            // COLUMNAS
            ->columns([
                Tables\Columns\TextColumn::make('numero_guia')
                    ->label('N° Guía')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('serie')
                    ->label('Serie')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('descripcion_completa')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('fecha_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            
            // FILTROS
            ->filters([
                Tables\Filters\Filter::make('fecha_emision')
                    ->label('Fecha')
                    ->form([
                        Forms\Components\DatePicker::make('fecha_desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('fecha_hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['fecha_desde'], fn($q) => $q->whereDate('fecha_emision', '>=', $data['fecha_desde']))
                            ->when($data['fecha_hasta'], fn($q) => $q->whereDate('fecha_emision', '<=', $data['fecha_hasta']));
                    }),
            ])
            
            // ACCIONES POR FILA
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            
            // ACCIONES MASIVAS
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    // Exportar seleccionados a Excel
                    Tables\Actions\BulkAction::make('exportar_seleccionados_excel')
                        ->label('Exportar seleccionados a Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->form([
                            Forms\Components\CheckboxList::make('columnas')
                                ->label('Columnas a exportar')
                                ->options([
                                    'numero_guia' => 'N° Guía',
                                    'producto_nombre' => 'Producto',
                                    'marca' => 'Marca',
                                    'modelo' => 'Modelo',
                                    'serie' => 'Serie',
                                    'descripcion_completa' => 'Descripción',
                                    'fecha_emision' => 'Fecha Emisión',
                                    'created_at' => 'Fecha Registro',
                                ])
                                ->default(['numero_guia', 'producto_nombre', 'serie', 'fecha_emision'])
                                ->columns(2)
                                ->label('Seleccione las columnas'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->load('producto');
                            $columnasSeleccionadas = $data['columnas'] ?? [];
                            $export = new GuiaRemisionExport($records, $columnasSeleccionadas);
                            return Excel::download($export, 'guias_remision_seleccionadas_' . now()->format('Ymd_His') . '.xlsx');
                        }),
                    
                    // Exportar seleccionados a PDF
                    Tables\Actions\BulkAction::make('exportar_seleccionados_pdf')
                        ->label('Exportar seleccionados a PDF')
                        ->icon('heroicon-o-document')
                        ->color('danger')
                        ->form([
                            Forms\Components\CheckboxList::make('columnas')
                                ->label('Columnas a exportar')
                                ->options([
                                    'numero_guia' => 'N° Guía',
                                    'producto_nombre' => 'Producto',
                                    'marca' => 'Marca',
                                    'modelo' => 'Modelo',
                                    'serie' => 'Serie',
                                    'descripcion_completa' => 'Descripción',
                                    'fecha_emision' => 'Fecha Emisión',
                                ])
                                ->default(['numero_guia', 'producto_nombre', 'serie', 'fecha_emision'])
                                ->columns(2)
                                ->label('Seleccione las columnas'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->load('producto');
                            $columnasSeleccionadas = $data['columnas'] ?? [];
                            $export = new GuiaRemisionPdfExport($records, $columnasSeleccionadas);
                            return $export->download();
                        }),
                ]),
            ])
            
            ->striped()
            ->emptyStateHeading('No hay guías de remisión')
            ->emptyStateDescription('Crea una nueva guía de remisión para comenzar.')
            ->emptyStateIcon('heroicon-o-truck');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuiasRemisions::route('/'),
            'create' => Pages\CreateGuiaRemision::route('/create'),
            'edit' => Pages\EditGuiaRemision::route('/{record}/edit'),
        ];
    }
}