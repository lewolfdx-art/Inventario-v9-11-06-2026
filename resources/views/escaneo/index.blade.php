<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner de Productos</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #1a3c6e;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .scanner-box {
            border: 3px dashed #ccc;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .scanner-box.focused {
            border-color: #2E7D32;
            background: #f0faf0;
        }
        .scanner-box .icon {
            font-size: 60px;
            margin-bottom: 10px;
        }
        .scanner-box p {
            color: #888;
            font-size: 16px;
        }
        .scanner-box input {
            width: 100%;
            padding: 15px 20px;
            font-size: 18px;
            border: 2px solid #ddd;
            border-radius: 12px;
            outline: none;
            text-align: center;
            letter-spacing: 2px;
            font-weight: bold;
            background: #fafafa;
            transition: all 0.3s;
        }
        .scanner-box input:focus {
            border-color: #2E7D32;
            background: white;
            box-shadow: 0 0 0 4px rgba(46, 125, 50, 0.1);
        }
        .scanner-box .info {
            margin-top: 15px;
            font-size: 13px;
            color: #888;
        }
        .scanner-box .info .modo {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
        }
        .scanner-box .info .modo.entrada {
            background: #e8f5e9;
            color: #2E7D32;
        }
        .scanner-box .info .modo.salida {
            background: #ffebee;
            color: #D32F2F;
        }
        .scanner-box .info .contador {
            color: #aaa;
            margin-left: 10px;
        }
        .btn-limpiar {
            margin-top: 15px;
            padding: 10px 30px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #757575;
            color: white;
            transition: all 0.3s;
        }
        .btn-limpiar:hover {
            background: #616161;
        }
        .resultado {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border-radius: 16px;
            background: #f8f9fa;
            text-align: left;
            animation: slideUp 0.3s ease;
        }
        .resultado.mostrar {
            display: block;
        }
        .resultado .producto-nombre {
            font-size: 22px;
            font-weight: bold;
            color: #1a3c6e;
        }
        .resultado .producto-sku {
            font-size: 14px;
            color: #888;
            margin-bottom: 10px;
        }
        .resultado .producto-detalle {
            font-size: 14px;
            color: #555;
            margin: 4px 0;
        }
        .resultado .producto-detalle span {
            font-weight: 600;
            color: #1a3c6e;
        }
        .resultado .stock {
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }
        .resultado .stock.verde {
            background: #e8f5e9;
            color: #2E7D32;
        }
        .resultado .stock.rojo {
            background: #ffebee;
            color: #D32F2F;
        }
        .resultado .stock.amarillo {
            background: #fff3e0;
            color: #E65100;
        }
        .notificacion {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: bold;
            font-size: 16px;
            display: none;
            animation: slideIn 0.3s ease;
            z-index: 1000;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .notificacion.exito {
            background: #2E7D32;
            display: block;
        }
        .notificacion.error {
            background: #D32F2F;
            display: block;
        }
        .notificacion.info {
            background: #1976D2;
            display: block;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            .resultado .producto-nombre {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🔍 SISTEMA DE INVENTARIO</div>
        <p class="subtitle">Escanea el código de barras para registrar movimientos</p>

        <div class="scanner-box" id="scannerBox">
            <div class="icon">📷</div>
            <p>Escanea el código de barras</p>
            <input type="text" id="scannerInput" placeholder="Código de barras..." autofocus>

            <div class="info">
                🔄 <span class="modo entrada" id="modoTexto">📥 ENTRADA</span>
                <span class="contador" id="contadorEscaneos">Escaneos: 0</span>
            </div>

            <button class="btn-limpiar" onclick="limpiar()">🗑️ Limpiar</button>
        </div>

        <div class="resultado" id="resultado">
            <div class="producto-nombre" id="productoNombre">-</div>
            <div class="producto-sku" id="productoSku">SKU: -</div>
            <div class="producto-detalle"><span>Marca:</span> <span id="productoMarca">-</span></div>
            <div class="producto-detalle"><span>Modelo:</span> <span id="productoModelo">-</span></div>
            <div class="producto-detalle"><span>Categoría:</span> <span id="productoCategoria">-</span></div>
            <div class="producto-detalle"><span>Estado:</span> <span id="productoEstado">-</span></div>
            <div class="stock verde" id="productoStock">Stock: 0</div>
        </div>

        <div class="footer">
            <p>Sistema de Inventario - {{ now()->year }}</p>
        </div>
    </div>

    <div class="notificacion" id="notificacion"></div>

    <script>
        let contadorEscaneos = 0;
        let modoActual = 'entrada';
        let ultimoProducto = null;

        const input = document.getElementById('scannerInput');
        const resultado = document.getElementById('resultado');
        const notificacion = document.getElementById('notificacion');
        const modoTexto = document.getElementById('modoTexto');
        const contadorEl = document.getElementById('contadorEscaneos');

        function mostrarNotificacion(mensaje, tipo) {
            notificacion.textContent = mensaje;
            notificacion.className = 'notificacion ' + tipo;
            setTimeout(() => {
                notificacion.className = 'notificacion';
            }, 3000);
        }

        function actualizarModo(contador) {
            if (contador % 2 === 1) {
                modoActual = 'salida';
                modoTexto.textContent = '📤 SALIDA';
                modoTexto.className = 'modo salida';
            } else {
                modoActual = 'entrada';
                modoTexto.textContent = '📥 ENTRADA';
                modoTexto.className = 'modo entrada';
            }
            contadorEl.textContent = 'Escaneos: ' + contador;
        }

        function mostrarProducto(data) {
            const producto = data.producto;
            ultimoProducto = producto;

            document.getElementById('productoNombre').textContent = producto.nombre;
            document.getElementById('productoSku').textContent = 'SKU: ' + producto.sku;
            document.getElementById('productoMarca').textContent = producto.marca?.nombre || 'N/A';
            document.getElementById('productoModelo').textContent = producto.modelo || 'N/A';
            document.getElementById('productoCategoria').textContent = producto.categoria?.nombre || 'N/A';
            document.getElementById('productoEstado').textContent = producto.estado?.nombre || 'N/A';

            const stock = producto.stock || 0;
            const stockEl = document.getElementById('productoStock');
            stockEl.textContent = 'Stock: ' + stock;

            if (stock <= 0) {
                stockEl.className = 'stock rojo';
            } else if (stock <= 5) {
                stockEl.className = 'stock amarillo';
            } else {
                stockEl.className = 'stock verde';
            }

            resultado.classList.add('mostrar');
        }

        function registrarMovimiento(sku) {
            fetch('{{ route("escaneo.registrar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    sku: sku
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    contadorEscaneos = data.contador;
                    actualizarModo(contadorEscaneos);

                    const icono = data.tipo === 'entrada' ? '📥' : '📤';
                    mostrarNotificacion(icono + ' ' + data.mensaje, 'exito');
                    mostrarProducto(data);
                } else {
                    mostrarNotificacion('❌ ' + data.message, 'error');
                }
            })
            .catch(err => {
                mostrarNotificacion('❌ Error al registrar', 'error');
            });
        }

        function procesarEscaneo() {
            let sku = input.value.trim();
            if (!sku) return;

            // Reemplazar apóstrofes
            sku = sku.replace(/['´`]/g, '-');

            input.value = '';
            input.focus();

            registrarMovimiento(sku);
        }

        // Evento: Enter en el input
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                procesarEscaneo();
            }
        });

        // Enfocar automáticamente al cargar
        document.addEventListener('DOMContentLoaded', function() {
            input.focus();
            // Cargar contador desde sesión
            fetch('{{ route("escaneo.contador") }}')
                .then(res => res.json())
                .then(data => {
                    if (data.contador) {
                        contadorEscaneos = data.contador;
                        actualizarModo(contadorEscaneos);
                    }
                })
                .catch(() => {});
        });

        // Click en el área del escáner para enfocar
        document.getElementById('scannerBox').addEventListener('click', function() {
            input.focus();
        });

        function limpiar() {
            resultado.classList.remove('mostrar');
            input.value = '';
            input.focus();
            ultimoProducto = null;
        }

        notificacion.addEventListener('click', function() {
            this.className = 'notificacion';
        });
    </script>
</body>
</html>