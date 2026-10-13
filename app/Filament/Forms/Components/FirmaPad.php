<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * Campo de firma: permite dibujar la firma con el mouse/dedo o pegarla como
 * imagen. El valor del campo es un "data URL" (base64) mientras el usuario la
 * dibuja/pega, o la ruta del archivo ya guardado cuando la firma no se toca.
 */
class FirmaPad extends Field
{
    protected string $view = 'filament.forms.components.firma-pad';
}
