<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold"> Equipos que necesitan recalibración</h2>
            <div class="flex gap-2">
                @if($vencidos > 0)
                    <span class="inline-flex items-center rounded-full bg-danger-100 px-3 py-1 text-sm text-danger-700">
                        🔴 {{ $vencidos }} vencidos
                    </span>
                @endif
                @if($hoy > 0)
                    <span class="inline-flex items-center rounded-full bg-warning-100 px-3 py-1 text-sm text-warning-700">
                        🟡 {{ $hoy }} hoy
                    </span>
                @endif
                @if($porVenir > 0)
                    <span class="inline-flex items-center rounded-full bg-info-100 px-3 py-1 text-sm text-info-700">
                        🟢 {{ $porVenir }} próximos
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4">
            @if($productos->isEmpty())
                <p class="text-gray-500 text-sm">✅ No hay equipos que necesiten recalibración próxima.</p>
            @else
                <div class="space-y-2">
                    @foreach($productos as $producto)
                        @php
                            $proxima = $producto->recalibraciones->first()?->proxima_recalibracion;
                            $dias = $hoyDate->diffInDays($proxima, false);
                            $color = $dias < 0 ? 'danger' : ($dias == 0 ? 'warning' : 'info');
                            $icon = $dias < 0 ? '🔴' : ($dias == 0 ? '🟡' : '🟢');
                            $texto = $dias < 0 ? 'Vencido hace ' . abs($dias) . ' días' : ($dias == 0 ? 'Hoy' : 'En ' . $dias . ' días');
                        @endphp
                        <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ $icon }}</span>
                                <div>
                                    <p class="font-medium">{{ $producto->nombre }}</p>
                                    <p class="text-sm text-gray-500">
                                        SKU: {{ $producto->sku }} | Modelo: {{ $producto->modelo }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-{{ $color }}-100 text-{{ $color }}-700">
                                    {{ $texto }}
                                </span>
                                <p class="text-xs text-gray-400 mt-1">
                                    Próxima: {{ \Carbon\Carbon::parse($proxima)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>