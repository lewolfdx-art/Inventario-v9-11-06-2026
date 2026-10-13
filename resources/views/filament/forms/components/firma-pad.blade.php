@php
    $estadoFirma = $getState();
    $firmaActual = '';

    if (is_string($estadoFirma) && $estadoFirma !== '') {
        $firmaActual = str_starts_with($estadoFirma, 'data:')
            ? $estadoFirma
            : \App\Support\Firma::base64($estadoFirma);
    }
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :has-inline-label="$hasInlineLabel()"
    :id="$getId()"
    :label="$getLabel()"
    :label-sr-only="$isLabelHidden()"
    :helper-text="$getHelperText()"
    :state-path="$getStatePath()"
>
    @if ($isDisabled())
        <div class="flex min-h-[130px] items-center justify-center rounded-lg border border-gray-200 bg-white p-2 dark:border-white/10 dark:bg-gray-900">
            @if ($firmaActual)
                <img
                    src="{{ $firmaActual }}"
                    alt="Firma"
                    class="max-h-32 w-auto object-contain"
                />
            @else
                <span class="text-sm text-gray-400 dark:text-gray-500">Sin firma registrada</span>
            @endif
        </div>
    @else
    <div
        x-data="{
            value: $wire.$entangle('{{ $getStatePath() }}'),
            urlActual: '{{ $firmaActual }}',
            imgSrc: '',
            tieneFirma: false,
            mensaje: '',
            dibujando: false,
            ajustando: false,
            ctx: null,
            img: null,
            s: 1,
            tx: 0,
            ty: 0,
            panActivo: false,
            lastX: 0,
            lastY: 0,
            init() {
                this.$nextTick(() => this.preparar());
            },
            rect() {
                return this.$refs.canvas.getBoundingClientRect();
            },
            preparar() {
                const canvas = this.$refs.canvas;
                const r = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                canvas.width = Math.round(r.width * ratio);
                canvas.height = Math.round(r.height * ratio);
                this.ctx = canvas.getContext('2d');
                this.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.strokeStyle = '#111827';
                this.limpiarFondo();
                if (this.urlActual) {
                    this.cargarImagen(this.urlActual, true);
                }
            },
            limpiarFondo() {
                const r = this.rect();
                this.ctx.fillStyle = '#ffffff';
                this.ctx.fillRect(0, 0, r.width, r.height);
            },
            pos(e) {
                const r = this.rect();
                return { x: e.clientX - r.left, y: e.clientY - r.top };
            },
            /* ===== Dibujo a mano ===== */
            empezar(e) {
                if (this.ajustando) return;
                e.preventDefault();
                this.dibujando = true;
                const p = this.pos(e);
                this.ctx.beginPath();
                this.ctx.moveTo(p.x, p.y);
                if (this.$refs.canvas.setPointerCapture && e.pointerId != null) {
                    this.$refs.canvas.setPointerCapture(e.pointerId);
                }
            },
            mover(e) {
                if (! this.dibujando || this.ajustando) return;
                e.preventDefault();
                const p = this.pos(e);
                this.ctx.lineTo(p.x, p.y);
                this.ctx.stroke();
            },
            terminar() {
                if (! this.dibujando) return;
                this.dibujando = false;
                this.guardar();
            },
            guardar() {
                this.value = this.recortarBlanco();
                if (! this.value) {
                    this.urlActual = '';
                    this.tieneFirma = false;

                    return;
                }
                this.urlActual = this.value;
                this.tieneFirma = true;
                this.mensaje = '';
            },
            /* Devuelve un data URL recortado al contenido (sin bordes blancos). */
            recortarBlanco() {
                const canvas = this.$refs.canvas;
                const w = canvas.width;
                const h = canvas.height;
                const data = this.ctx.getImageData(0, 0, w, h).data;
                let minX = w;
                let minY = h;
                let maxX = -1;
                let maxY = -1;

                for (let y = 0; y < h; y++) {
                    for (let x = 0; x < w; x++) {
                        const i = (y * w + x) * 4;
                        const r = data[i];
                        const g = data[i + 1];
                        const b = data[i + 2];
                        const a = data[i + 3];
                        if (a > 12 && (r < 244 || g < 244 || b < 244)) {
                            if (x < minX) minX = x;
                            if (x > maxX) maxX = x;
                            if (y < minY) minY = y;
                            if (y > maxY) maxY = y;
                        }
                    }
                }

                if (maxX < 0) {
                    return null;
                }

                const pad = Math.round(10 * (window.devicePixelRatio || 1));
                minX = Math.max(0, minX - pad);
                minY = Math.max(0, minY - pad);
                maxX = Math.min(w - 1, maxX + pad);
                maxY = Math.min(h - 1, maxY + pad);

                const cw = maxX - minX + 1;
                const ch = maxY - minY + 1;
                const out = document.createElement('canvas');
                out.width = cw;
                out.height = ch;
                const octx = out.getContext('2d');
                octx.drawImage(canvas, minX, minY, cw, ch, 0, 0, cw, ch);

                return out.toDataURL('image/png');
            },
            limpiar() {
                this.cancelarAjuste();
                this.limpiarFondo();
                this.value = null;
                this.urlActual = '';
                this.tieneFirma = false;
                this.mensaje = '';
            },
            cargarImagen(url, marcar) {
                const self = this;
                const img = new Image();
                img.onload = function () {
                    const r = self.rect();
                    const e = Math.min(r.width / img.width, r.height / img.height, 1);
                    const w = img.width * e;
                    const h = img.height * e;
                    self.ctx.drawImage(img, (r.width - w) / 2, (r.height - h) / 2, w, h);
                    if (marcar) self.tieneFirma = true;
                };
                img.src = url;
            },
            /* ===== Modo ajuste de imagen (subir/pegar) ===== */
            iniciarAjuste(archivoOUrl) {
                const self = this;
                const esBlob = typeof archivoOUrl !== 'string';
                const url = esBlob ? URL.createObjectURL(archivoOUrl) : archivoOUrl;
                const img = new Image();
                img.onload = function () {
                    self.img = img;
                    self.imgSrc = url;
                    const r = self.rect();
                    const e = Math.min(r.width / img.width, r.height / img.height, 1);
                    self.s = e;
                    self.tx = (r.width - img.width * e) / 2;
                    self.ty = (r.height - img.height * e) / 2;
                    self.ajustando = true;
                    self.mensaje = '';
                };
                img.onerror = function () {
                    if (esBlob) URL.revokeObjectURL(url);
                    self.mensaje = 'No se pudo cargar la imagen.';
                };
                img.src = url;
            },
            cancelarAjuste() {
                if (this.imgSrc && this.imgSrc.indexOf('blob:') === 0) {
                    URL.revokeObjectURL(this.imgSrc);
                }
                this.img = null;
                this.imgSrc = '';
                this.ajustando = false;
                this.panActivo = false;
            },
            aplicarImagen() {
                if (! this.img) return;
                this.ctx.drawImage(this.img, this.tx, this.ty, this.img.width * this.s, this.img.height * this.s);
                this.cancelarAjuste();
                this.guardar();
            },
            zoom(factor) {
                const r = this.rect();
                const cx = r.width / 2;
                const cy = r.height / 2;
                this.tx = cx - (cx - this.tx) * factor;
                this.ty = cy - (cy - this.ty) * factor;
                this.s = this.s * factor;
            },
            panStart(e) {
                this.panActivo = true;
                this.lastX = e.clientX;
                this.lastY = e.clientY;
                if (this.$refs.imgPreview.setPointerCapture && e.pointerId != null) {
                    this.$refs.imgPreview.setPointerCapture(e.pointerId);
                }
                e.preventDefault();
            },
            panMove(e) {
                if (! this.panActivo) return;
                this.tx += e.clientX - this.lastX;
                this.ty += e.clientY - this.lastY;
                this.lastX = e.clientX;
                this.lastY = e.clientY;
                e.preventDefault();
            },
            panEnd() {
                this.panActivo = false;
            },
            estiloImagen() {
                return 'transform: translate(' + this.tx + 'px,' + this.ty + 'px) scale(' + this.s + '); transform-origin: top left;';
            },
            subirArchivo(e) {
                const f = e.target.files && e.target.files[0];
                if (f) this.iniciarAjuste(f);
                e.target.value = '';
            },
            /* ===== Pegar desde el portapapeles ===== */
            procesarBlob(blob) {
                this.iniciarAjuste(blob);
            },
            pegarEvento(e) {
                const items = (e.clipboardData && e.clipboardData.items) || [];
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type && items[i].type.indexOf('image/') === 0) {
                        const blob = items[i].getAsFile();
                        if (blob) {
                            e.preventDefault();
                            this.procesarBlob(blob);
                            return;
                        }
                    }
                }
                this.mensaje = 'El portapapeles no contiene una imagen.';
            },
            async pegarBoton() {
                this.mensaje = '';
                try {
                    if (! navigator.clipboard || ! navigator.clipboard.read) {
                        this.mensaje = 'Tu navegador no permite pegar. Usa Ctrl+V sobre el recuadro.';
                        return;
                    }
                    const items = await navigator.clipboard.read();
                    for (const item of items) {
                        const tipo = item.types.find(function (t) { return t.indexOf('image/') === 0; });
                        if (tipo) {
                            this.procesarBlob(await item.getType(tipo));
                            return;
                        }
                    }
                    this.mensaje = 'El portapapeles no contiene una imagen.';
                } catch (err) {
                    this.mensaje = 'No se pudo leer el portapapeles. Usa Ctrl+V sobre el recuadro.';
                }
            },
        }"
        class="space-y-2"
    >
        <div
            tabindex="0"
            @paste.prevent="pegarEvento($event)"
            class="relative overflow-hidden rounded-lg border border-gray-300 bg-white outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-gray-900"
            style="height: 160px;"
        >
            <canvas
                x-ref="canvas"
                class="absolute inset-0 block h-full w-full"
                :style="ajustando ? 'pointer-events: none;' : 'cursor: crosshair; touch-action: none;'"
                @pointerdown="empezar($event)"
                @pointermove="mover($event)"
                @pointerup="terminar()"
                @pointerleave="terminar()"
                @pointercancel="terminar()"
            ></canvas>

            <img
                x-ref="imgPreview"
                :src="imgSrc"
                x-show="ajustando"
                x-cloak
                draggable="false"
                alt=""
                class="absolute left-0 top-0 max-w-none select-none"
                :style="estiloImagen()"
                style="cursor: move; touch-action: none;"
                @pointerdown="panStart($event)"
                @pointermove="panMove($event)"
                @pointerup="panEnd()"
                @pointerleave="panEnd()"
                @pointercancel="panEnd()"
            />
        </div>

        <input type="file" accept="image/*" class="hidden" x-ref="file" @change="subirArchivo($event)" />

        <div class="flex flex-wrap items-center gap-2">
            <x-filament::button type="button" color="primary" size="sm" icon="heroicon-o-arrow-up-tray" x-on:click="$refs.file.click()">
                Subir imagen
            </x-filament::button>

            <x-filament::button type="button" color="gray" size="sm" icon="heroicon-o-clipboard-document" x-on:click="pegarBoton()">
                Pegar imagen
            </x-filament::button>

            <template x-if="ajustando">
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button type="button" color="gray" size="sm" icon="heroicon-o-minus" x-on:click="zoom(0.85)" />
                    <x-filament::button type="button" color="gray" size="sm" icon="heroicon-o-plus" x-on:click="zoom(1.15)" />
                    <x-filament::button type="button" color="success" size="sm" icon="heroicon-o-check" x-on:click="aplicarImagen()">
                        Aplicar
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" size="sm" x-on:click="cancelarAjuste()">
                        Cancelar
                    </x-filament::button>
                </div>
            </template>

            <x-filament::button type="button" color="danger" size="sm" icon="heroicon-o-backspace" x-on:click="limpiar()">
                Limpiar
            </x-filament::button>

            <span class="text-xs text-success-600 dark:text-success-400" x-show="tieneFirma && ! ajustando" x-cloak>
                Firma capturada
            </span>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400" x-show="ajustando" x-cloak>
            Usa los botones <strong>−</strong> / <strong>+</strong> para el zoom y arrastra la imagen para encuadrar la firma. Luego pulsa <strong>Aplicar</strong>.
        </p>

        <p class="text-xs text-danger-600 dark:text-danger-400" x-show="mensaje" x-cloak x-text="mensaje"></p>
    </div>
    @endif
</x-dynamic-component>
