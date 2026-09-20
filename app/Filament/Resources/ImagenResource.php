<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImagenResource\Pages;
use App\Models\Imagen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;

class ImagenResource extends Resource
{
    protected static ?string $model = Imagen::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Imágenes';
    protected static ?string $pluralLabel = 'Imágenes';
    protected static ?string $label = 'Imagen';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos de la imagen')
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ej: Logo Empresa'),

                        Select::make('tipo')
                            ->label('Tipo de imagen')
                            ->options([
                                'logo' => '🖼️ Logo',
                                'firma' => '✍️ Firma',
                                'sello' => '🔴 Sello',
                                'marca_agua' => '💧 Marca de agua',
                                'otro' => '📎 Otro',
                            ])
                            ->default('logo')
                            ->required()
                            ->reactive(),

                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Descripción opcional de la imagen...'),

                        Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Si está desactivado, no aparecerá en los documentos'),
                    ])->columns(2),

                Section::make('Archivo')
                    ->schema([
                        FileUpload::make('archivo')
                            ->label('Subir imagen')
                            ->image()
                            ->required()
                            ->directory('imagenes')
                            ->visibility('public')
                            ->maxSize(5120) // 5MB
                            ->imagePreviewHeight('250')
                            ->loadingIndicatorPosition('left')
                            ->panelAspectRatio('2:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/webp'])
                            ->helperText('Formatos permitidos: PNG, JPG, SVG, WEBP (máx. 5MB)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('archivo')
                    ->label('Vista previa')
                    ->height(60)
                    ->width(60)
                    ->circular(false)
                    ->defaultImageUrl(url('/images/placeholder.png')),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'logo' => 'primary',
                        'firma' => 'success',
                        'sello' => 'danger',
                        'marca_agua' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'logo' => '🖼️ Logo',
                        'firma' => '✍️ Firma',
                        'sello' => '🔴 Sello',
                        'marca_agua' => '💧 Marca de agua',
                        default => '📎 Otro',
                    })
                    ->sortable(),

                TextColumn::make('mime_type')
                    ->label('Tipo MIME')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tamaño')
                    ->label('Tamaño')
                    ->formatStateUsing(fn($state) => $state ? number_format($state / 1024, 2) . ' KB' : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'logo' => '🖼️ Logo',
                        'firma' => '✍️ Firma',
                        'sello' => '🔴 Sello',
                        'marca_agua' => '💧 Marca de agua',
                        'otro' => '📎 Otro',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                // ✅ Copiar URL de la imagen
                Tables\Actions\Action::make('copiar_url')
                    ->label('Copiar URL')
                    ->icon('heroicon-o-clipboard')
                    ->color('info')
                    ->action(function (Imagen $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('URL de la imagen')
                            ->body($record->url)
                            ->info()
                            ->persistent()
                            ->send();
                    }),

                // ✅ Ver imagen completa
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->url(fn(Imagen $record) => $record->url)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('No hay imágenes')
            ->emptyStateDescription('Sube tu primera imagen para usarla en los documentos.')
            ->emptyStateIcon('heroicon-o-photo');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImagens::route('/'),
            'create' => Pages\CreateImagen::route('/create'),
            'edit' => Pages\EditImagen::route('/{record}/edit'),
        ];
    }
}