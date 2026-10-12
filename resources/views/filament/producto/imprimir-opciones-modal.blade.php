<style>
    .unid-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.6rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        text-decoration: none;
        border: 2px solid transparent;
        cursor: pointer;
    }
    .unid-btn:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }
    .unid-btn-todas {
        background-color: #3b82f6;
        color: #ffffff;
    }
    .unid-btn-todas:hover {
        background-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    .unid-btn-unidad {
        background-color: #e5e7eb;
        color: #374151;
    }
    .unid-btn-unidad:hover {
        background-color: #d1d5db;
        color: #111827;
    }
    .unid-btn-zebra {
        background-color: #22c55e;
        color: #ffffff;
    }
    .unid-btn-zebra:hover {
        background-color: #16a34a;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
    }
</style>

@php
    $producto = $record instanceof \App\Models\Unidad ? $record->producto : $record;
    $unidades = $producto->unidades ?? collect();
@endphp

<div class="unid-modal flex flex-col gap-3 p-4">
    <div class="text-center text-gray-600 dark:text-gray-400 mb-1">
        ¿Qué deseas imprimir de <strong>{{ $producto->nombre }}</strong>?
    </div>

    @if($tipo === 'zebra')
        <a href="{{ route('etiqueta.abrir', $producto) }}" target="_blank" class="unid-btn unid-btn-todas">
            📥 Abrir Direct Communication (todas)
        </a>
        <a href="{{ route('etiqueta.descargar', $producto) }}" target="_blank" class="unid-btn unid-btn-todas">
            🖨️ Descargar ZPL y abrir Direct Comm (todas)
        </a>
    @else
        @php
            $rutaTodas = match ($tipo) {
                'etiqueta' => route('etiqueta.producto', $producto),
                'zpl' => route('etiqueta-zpl.producto', $producto),
                default => '#',
            };
        @endphp
        <a href="{{ $rutaTodas }}" target="_blank" class="unid-btn unid-btn-todas">
            🖨️ Imprimir todas ({{ $producto->sku }})
        </a>
    @endif

    @if($unidades->count())
        <div class="text-xs text-gray-500 text-center border-t border-gray-200 dark:border-gray-700 pt-2">
            Tiradas individuales por unidad
        </div>

        @foreach($unidades as $unidad)
            @php
                $rutaUnidad = match ($tipo) {
                    'etiqueta' => route('etiqueta.unidad', $unidad),
                    'zpl' => route('etiqueta-zpl.unidad', $unidad),
                    'zebra' => route('etiqueta.unidad.abrir', $unidad),
                    default => '#',
                };
            @endphp
            <a href="{{ $rutaUnidad }}" target="_blank" class="unid-btn {{ $tipo === 'zebra' ? 'unid-btn-zebra' : 'unid-btn-unidad' }}">
                Unidad {{ $unidad->numero }} ({{ $unidad->codigo }})
            </a>
        @endforeach
    @endif
</div>