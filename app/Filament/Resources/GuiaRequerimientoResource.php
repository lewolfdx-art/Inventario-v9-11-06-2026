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
    // FORMULARIO
    // ==========================================
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Control Documental')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('codigo')
                        ->default('LOG-FOR-001')
                        ->required()
                        ->maxLength(50),

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

            Forms\Components\Section::make('Ítems del Requerimiento')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->columns(12)
                        ->defaultItems(1)
                        ->reorderable('orden')
                        ->addActionLabel('Agregar ítem')
                        ->schema([
                            Forms\Components\TextInput::make('item')
                                ->label('N°')
                                ->numeric()
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('descripcion')
                                ->required()
                                ->maxLength(500)
                                ->columnSpan(5)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                            Forms\Components\TextInput::make('cantidad_solicitada')
                                ->label('Cant.')
                                ->numeric()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('unidad_solicitada')
                                ->label('Und.')
                                ->maxLength(20)
                                ->columnSpan(2)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                            Forms\Components\Toggle::make('entregado')
                                ->label('Entregado')
                                ->inline(false)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('cantidad_entregada')
                                ->label('Cant. Entreg.')
                                ->numeric()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('unidad_entregada')
                                ->label('Und. Entreg.')
                                ->maxLength(20)
                                ->columnSpan(2)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                            Forms\Components\Toggle::make('devuelto')
                                ->label('Devuelto')
                                ->inline(false)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('cantidad_devuelta')
                                ->label('Cant. Dev.')
                                ->numeric()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('unidad_devuelta')
                                ->label('Und. Dev.')
                                ->maxLength(20)
                                ->columnSpan(2)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),
                        ]),
                ]),

            Forms\Components\Section::make('Firmas')
                ->schema([
                    Forms\Components\Repeater::make('firmas')
                        ->relationship()
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('Agregar firma')
                        ->schema([
                            Forms\Components\Select::make('tipo')
                                ->options([
                                    'atendido_por'         => 'ATENDIDO POR',
                                    'autorizado_por'       => 'AUTORIZADO POR',
                                    'recibi_conforme'      => 'RECIBÍ CONFORME',
                                    'recepcion_devolucion' => 'RECEPCIÓN - DEVOLUCIÓN',
                                ])
                                ->required(),

                            Forms\Components\DatePicker::make('fecha'),

                            Forms\Components\TextInput::make('nombre_apellidos')
                                ->maxLength(150)
                                ->dehydrateStateUsing(fn ($state) => static::cleanUtf8($state)),

                            Forms\Components\TextInput::make('dni')
                                ->maxLength(15)
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