<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold"> Herramientas con stock bajo</h2>
            <div class="flex gap-2">
                @if($totalSinStock > 0)
                    <span class="inline-flex items-center rounded-full bg-danger-100 px-3 py-1 text-sm text-danger-700">
                        ⚠️ {{ $totalSinStock }} sin stock
                    </span>
                @endif
                @if($totalBajo > 0)
                    <span class="inline-flex items-center rounded-full bg-warning-100 px-3 py-1 text-sm text-warning-700">
                        📉 {{ $totalBajo }} con stock bajo
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">
            @if($sinStock->isNotEmpty())
                <div class="border-r pr-4">
                    <h3 class="text-sm font-semibold text-danger-600 mb-2">🚫 Sin stock</h3>
                    <ul class="space-y-1">
                        @foreach($sinStock as $producto)
                            <li class="text-sm flex justify-between">
                                <span>{{ $producto->nombre }}</span>
                                <span class="text-danger-600 font-bold">0</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($productos->isNotEmpty())
                <div>
                    <h3 class="text-sm font-semibold text-warning-600 mb-2">⚠️ Stock bajo (≤ 5)</h3>
                    <ul class="space-y-1">
                        @foreach($productos as $producto)
                            <li class="text-sm flex justify-between">
                                <span>{{ $producto->nombre }}</span>
                                <span class="text-warning-600 font-bold">{{ $producto->stock }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if($productos->isEmpty() && $sinStock->isEmpty())
            <p class="text-gray-500 text-sm mt-4">✅ Todos los productos tienen stock suficiente.</p>
        @endif
    </x-filament::card>
</x-filament::widget>