<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuiaRequerimientoResource\Pages;
use App\Models\GuiaRequerimiento;
use App\Exports\GuiaRequerimientoPdfExport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class GuiaRequerimientoResource extends Resource
{
    protected static ?string $model = GuiaRequerimiento::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Guías de Requerimiento';

    protected static ?string $modelLabel = 'Guía de Requerimiento';

    protected static ?string $pluralModelLabel = 'Guías de Requerimiento';

    protected static ?string $navigationGroup = 'Guías';

    // ==========================================
    // Helper para limpiar UTF-8
    // ==========================================
    protected static function cleanUtf8($state)
    {
        if (!is_string($state)) {
            return $state;
        }

        if (!mb_check_encoding($state, 'UTF-8')) {
            $state = mb_convert_encoding($state, 'UTF-8', 'UTF-8');
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $state);
    }

    // ==========================================
    // Helper para generar el siguiente código
    // ==========================================
    public static function generarSiguienteCodigo(): string
    {
        $ultimo = \App\Models\GuiaRequerimiento::query()
            ->where('codigo', 'like', 'LOG-FOR-%')
            ->orderByRaw('CAST(SUBSTRING(codigo, 9) AS UNSIGNED) DESC')
            ->first();

        if (!$ultimo) {
            return 'LOG-FOR-001';
        }

        $numero = (int) substr($ultimo->codigo, 8);
        $siguiente = $numero + 1;

        return 'LOG-FOR-' . str_pad($siguiente, 3, '0', STR_PAD_LEFT);
    }

    // ==========================================
    // FORMULARIO
    // ==========================================
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Control Documental')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('codigo')
                        ->label('Código')
                        ->default(function () {
                            if (session('guia_codigo_automatico', true)) {
                                return static::generarSiguienteCodigo();
                            }
                            return 'LOG-FOR-001';
                        })
                        ->required()
                        ->maxLength(50)
                        ->readOnly(fn () => session('guia_codigo_automatico', true))
                        ->helperText(fn () => session('guia_codigo_automatico', true)
                            ? '🔒 Código generado automáticamente. Desactiva el switch en la lista para editarlo.'
                            : '🔓 Modo manual: escribe el código que quieras.'
                        ),

                    Forms\Components\TextInput::make('version')
                        ->default('02')
                        ->maxLength(10),

                    Forms\Components\DatePicker::make('fecha_documento')
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('revisado_por')
                        ->default('JEFE SIG')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('aprobado_por')
                        ->default('GG')
                        ->maxLength(100),

                    Forms\Components\Select::make('estado')
                        ->options([
                            'borrador'  => 'Borrador',
                            'pendiente' => 'Pendiente',
                            'aprobado'  => 'Aprobado',
                            'entregado' => 'Entregado',
                            'devuelto'  => 'Devuelto',
                        ])
                        ->default('borrador')
                        ->required(),
                ]),

            Forms\Components\Section::make('Datos del Proyecto')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nombre_proyecto')
                        ->label('Nombre del Proyecto / Servicio / Área')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                    Forms\Components\TextInput::make('responsable_solicitante')
                        ->label('Responsable Solicitante')
                        ->required()
                        ->maxLength(255)
                        ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                    Forms\Components\TextInput::make('centro_costos')
                        ->maxLength(50)
                        ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                    Forms\Components\DatePicker::make('fecha_pedido'),

                    Forms\Components\DatePicker::make('fecha_atencion'),
                ]),

            // ✅ SECCIÓN: MENCIONES / SUBTÍTULOS
            Forms\Components\Section::make('Menciones / Subtítulos')
                ->description('Texto que aparecerá entre los datos del proyecto y la tabla de ítems.')
                ->schema([
                    Forms\Components\Repeater::make('menciones')
                        ->relationship()
                        ->columns(12)
                        ->defaultItems(0)
                        ->addActionLabel('Agregar mención')
                        ->schema([
                            Forms\Components\Textarea::make('texto')
                                ->label('Texto de la mención')
                                ->required()
                                ->rows(2)
                                ->maxLength(500)
                                ->columnSpan(11)
                                ->placeholder('Ej: Se solicita la siguiente lista de materiales para el proyecto...')
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                            Forms\Components\Toggle::make('activo')
                                ->label('Activo')
                                ->default(true)
                                ->inline(false)
                                ->columnSpan(1),
                        ]),
                ]),

            // ✅ ÍTEMS DEL REQUERIMIENTO (SEPARADOS VISUALMENTE)
            Forms\Components\Section::make('Ítems del Requerimiento')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->columns(12)
                        ->defaultItems(1)
                        ->reorderable('orden')
                        ->addActionLabel('Agregar ítem')
                        ->schema([
                            // ==========================================
                            // 🟦 SOLICITADO
                            // ==========================================
                            Forms\Components\Fieldset::make('📋 SOLICITADO')
                                ->columns(12)
                                ->schema([
                                    Forms\Components\Select::make('producto_id')
                                        ->label('🔍 Buscar herramienta')
                                        ->placeholder('Escribe nombre, SKU o modelo...')
                                        ->searchable()
                                        ->preload()
                                        ->optionsLimit(20)
                                        ->columnSpan(6)
                                        ->getSearchResultsUsing(function (string $search) {
                                            return \App\Models\Producto::query()
                                                ->where(function ($q) use ($search) {
                                                    $q->where('nombre', 'like', "%{$search}%")
                                                      ->orWhere('sku', 'like', "%{$search}%")
                                                      ->orWhere('modelo', 'like', "%{$search}%");
                                                })
                                                ->limit(20)
                                                ->get()
                                                ->mapWithKeys(function ($producto) {
                                                    return [
                                                        $producto->id => "{$producto->sku} - {$producto->nombre} (Stock: {$producto->stock})"
                                                    ];
                                                })
                                                ->toArray();
                                        })
                                        ->getOptionLabelUsing(function ($value) {
                                            $producto = \App\Models\Producto::find($value);
                                            return $producto ? "{$producto->sku} - {$producto->nombre} (Stock: {$producto->stock})" : null;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state) {
                                                $producto = \App\Models\Producto::find($state);
                                                if ($producto) {
                                                    $set('descripcion', $producto->nombre);
                                                }
                                            }
                                        }),

                                    Forms\Components\TextInput::make('descripcion')
                                        ->label('Descripción-Se autocompleta al seleccionar un producto')
                                        ->required(fn (Forms\Get $get) => blank($get('producto_id')))
                                        ->maxLength(500)
                                        ->columnSpan(6)
                                        ->disabled(fn (Forms\Get $get) => filled($get('producto_id')))
                                        ->dehydrated()
                                        ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                                    Forms\Components\TextInput::make('item')
                                        ->label('N°')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('cantidad_solicitada')
                                        ->label('Cant.')
                                        ->numeric()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('unidad_solicitada')
                                        ->label('Und.')
                                        ->maxLength(20)
                                        ->columnSpan(1)
                                        ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),
                                ]),

                            // ==========================================
                            // 🟩 ENTREGADO
                            // ==========================================
                            Forms\Components\Fieldset::make('📤 ENTREGADO')
                                ->columns(12)
                                ->schema([
                                    Forms\Components\Toggle::make('entregado')
                                        ->label('Entregado')
                                        ->inline(false)
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('cantidad_entregada')
                                        ->label('Cant. Entreg.')
                                        ->numeric()
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('unidad_entregada')
                                        ->label('Und. Entreg.')
                                        ->maxLength(20)
                                        ->columnSpan(3)
                                        ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),
                                ]),

                            // ==========================================
                            // 🟥 DEVOLUCIÓN
                            // ==========================================
                            Forms\Components\Fieldset::make('📥 DEVOLUCIÓN')
                                ->columns(12)
                                ->schema([
                                    Forms\Components\Toggle::make('devuelto')
                                        ->label('Devuelto')
                                        ->inline(false)
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('cantidad_devuelta')
                                        ->label('Cant. Dev.')
                                        ->numeric()
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('unidad_devuelta')
                                        ->label('Und. Dev.')
                                        ->maxLength(20)
                                        ->columnSpan(3)
                                        ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),
                                ]),
                        ]),
                ]),

            // ==========================================
            // 🤝 FIRMAS DE ENTREGA (3 columnas)
            // ==========================================
            Forms\Components\Section::make('🤝 Firmas de Entrega')
                ->description('Firmas de quienes reciben y autorizan la entrega.')
                ->schema([
                    Forms\Components\Repeater::make('firmasEntrega')
                        ->relationship('firmasEntrega')
                        ->columns(3)
                        ->defaultItems(3)
                        ->default([
                            ['tipo' => 'atendido_por'],
                            ['tipo' => 'autorizado_por'],
                            ['tipo' => 'recibi_conforme'],
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Forms\Components\Select::make('tipo')
                                ->label('Tipo')
                                ->options([
                                    'atendido_por'    => 'ATENDIDO POR',
                                    'autorizado_por'  => 'AUTORIZADO POR',
                                    'recibi_conforme' => 'RECIBÍ CONFORME',
                                ])
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(1),

                            Forms\Components\DatePicker::make('fecha')
                                ->label('Fecha')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('nombre_apellidos')
                                ->label('Nombre y Apellidos')
                                ->maxLength(150)
                                ->columnSpan(1)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                            Forms\Components\TextInput::make('dni')
                                ->label('DNI')
                                ->maxLength(15)
                                ->columnSpan(1)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),
                        ]),
                ]),

            // ==========================================
            // 📦 FIRMA DE DEVOLUCIÓN (1 columna)
            // ==========================================
            Forms\Components\Section::make('📦 Firma de Devolución')
                ->description('Firma de quien recepciona la devolución.')
                ->schema([
                    Forms\Components\Repeater::make('firmaDevolucion')
                        ->relationship('firmaDevolucion')
                        ->columns(3)
                        ->defaultItems(1)
                        ->default([
                            ['tipo' => 'recepcion_devolucion'],
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Forms\Components\Select::make('tipo')
                                ->label('Tipo')
                                ->options([
                                    'recepcion_devolucion' => 'RECEPCIÓN - DEVOLUCIÓN',
                                ])
                                ->default('recepcion_devolucion')
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(1),

                            Forms\Components\DatePicker::make('fecha')
                                ->label('Fecha')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('nombre_apellidos')
                                ->label('Nombre y Apellidos')
                                ->maxLength(150)
                                ->columnSpan(1)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                            Forms\Components\TextInput::make('dni')
                                ->label('DNI')
                                ->maxLength(15)
                                ->columnSpan(1)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),
                        ]),
                ]),

            Forms\Components\Section::make('Comentarios / Observaciones')
                ->schema([
                    Forms\Components\Textarea::make('comentarios')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull()
                        ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),
                ]),
        ]);
    }

    // ==========================================
    // TABLA
    // ==========================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->label('Proyecto')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('responsable_solicitante')
                    ->label('Solicitante')
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_pedido')
                    ->label('F. Pedido')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_atencion')
                    ->label('F. Atención')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->colors([
                        'gray'    => 'borrador',
                        'warning' => 'pendiente',
                        'info'    => 'aprobado',
                        'success' => 'entregado',
                        'danger'  => 'devuelto',
                    ]),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Ítems')
                    ->counts('items')
                    ->badge(),

                Tables\Columns\TextColumn::make('menciones_count')
                    ->label('Menciones')
                    ->counts('menciones')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'borrador'  => 'Borrador',
                        'pendiente' => 'Pendiente',
                        'aprobado'  => 'Aprobado',
                        'entregado' => 'Entregado',
                        'devuelto'  => 'Devuelto',
                    ]),
            ])
            ->actions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (GuiaRequerimiento $record) => route('guia-requerimiento.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ==========================================
    // RELACIONES
    // ==========================================
    public static function getRelations(): array
    {
        return [];
    }

    // ==========================================
    // PÁGINAS
    // ==========================================
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGuiaRequerimientos::route('/'),
            'create' => Pages\CreateGuiaRequerimiento::route('/create'),
            'edit'   => Pages\EditGuiaRequerimiento::route('/{record}/edit'),
        ];
    }
}