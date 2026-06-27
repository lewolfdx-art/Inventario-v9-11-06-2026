{{-- resources/views/components/image-preview.blade.php --}}

@if($imagen)
    <div class="flex justify-center items-center p-4">
        <img 
            src="{{ $imagen }}" 
            alt="{{ $nombre ?? 'Imagen' }}"
            class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-xl"
            style="border: 2px solid #e5e7eb;"
        />
    </div>
@else
    <div class="text-center text-gray-500 p-8">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <p class="text-lg">No hay imagen disponible</p>
    </div>
@endif