<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner de Inventario</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #282c8c;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            padding: 45px;
            max-width: 650px;
            width: 100%;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            color: #888;
            margin-bottom: 30px;
            font-size: 15px;
            font-weight: 400;
        }
        
        .scanner-box {
            border: 3px dashed #d1d5db;
            border-radius: 20px;
            padding: 35px 30px;
            margin-bottom: 25px;
            transition: all 0.4s ease;
            background: #fafbfc;
            position: relative;
        }
        
        .scanner-box.focused {
            border-color: #667eea;
            background: #f0f4ff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .scanner-box .icon {
            font-size: 64px;
            margin-bottom: 12px;
            display: block;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .scanner-box p {
            color: #6b7280;
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .scanner-box input {
            width: 100%;
            padding: 16px 20px;
            font-size: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            outline: none;
            text-align: center;
            letter-spacing: 3px;
            font-weight: 700;
            background: white;
            transition: all 0.3s ease;
            color: #1f2937;
            font-family: 'Courier New', monospace;
        }
        
        .scanner-box input:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            transform: scale(1.01);
        }
        
        .scanner-box input::placeholder {
            letter-spacing: 1px;
            font-weight: 400;
            color: #9ca3af;
        }
        
        .info-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 18px;
            flex-wrap: wrap;
        }
        
        .modo-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .modo-badge.entrada {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .modo-badge.salida {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .contador-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 50px;
            background: #f3f4f6;
            color: #4b5563;
            font-weight: 600;
            font-size: 14px;
        }
        
        .contador-badge span {
            font-weight: 800;
            color: #1f2937;
            font-size: 18px;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 18px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-limpiar {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .btn-limpiar:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .btn-escanear {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-escanear:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.5);
        }
        
        .btn-escanear:active {
            transform: translateY(0);
        }
        
        /* Estadísticas */
        .stats-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 18px 20px;
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        
        .stat-card .stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }
        
        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .stat-card .stat-sub {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 2px;
        }
        
        .stat-card.entrada .stat-number {
            color: #10b981;
        }
        
        .stat-card.salida .stat-number {
            color: #ef4444;
        }
        
        .stat-card.entrada {
            border-left: 4px solid #10b981;
        }
        
        .stat-card.salida {
            border-left: 4px solid #ef4444;
        }
        
        /* Resultado producto */
        .resultado {
            display: none;
            margin-top: 25px;
            padding: 20px 24px;
            border-radius: 18px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            text-align: left;
            animation: slideUp 0.4s ease;
            border: 1px solid #e2e8f0;
        }
        
        .resultado.mostrar {
            display: block;
        }
        
        .resultado .producto-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }
        
        .resultado .producto-nombre {
            font-size: 20px;
            font-weight: 800;
            color: #1e293b;
        }
        
        .resultado .producto-sku {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 600;
            background: #e2e8f0;
            padding: 2px 12px;
            border-radius: 20px;
        }
        
        .resultado .producto-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 20px;
            margin: 10px 0 12px 0;
        }
        
        .resultado .producto-detalle {
            font-size: 14px;
            color: #475569;
            padding: 2px 0;
        }
        
        .resultado .producto-detalle span:first-child {
            font-weight: 600;
            color: #64748b;
        }
        
        .resultado .stock {
            font-size: 22px;
            font-weight: 800;
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            margin-top: 8px;
        }
        
        .resultado .stock.verde {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .resultado .stock.rojo {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .resultado .stock.amarillo {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        
        /* Notificaciones */
        .notificacion {
            position: fixed;
            top: 30px;
            right: 30px;
            padding: 16px 28px;
            border-radius: 16px;
            color: white;
            font-weight: 700;
            font-size: 15px;
            display: none;
            animation: slideIn 0.4s ease;
            z-index: 1000;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            max-width: 400px;
        }
        
        .notificacion.exito {
            background: linear-gradient(135deg, #10b981, #059669);
            display: block;
        }
        
        .notificacion.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            display: block;
        }
        
        .notificacion.info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: block;
        }
        
        .notificacion.advertencia {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            display: block;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #d1d5db;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
            letter-spacing: 0.5px;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @media (max-width: 500px) {
            .container {
                padding: 25px 20px;
            }
            .logo {
                font-size: 24px;
            }
            .stats-container {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .stat-card .stat-number {
                font-size: 24px;
            }
            .resultado .producto-grid {
                grid-template-columns: 1fr;
            }
            .info-container {
                flex-direction: column;
                gap: 10px;
            }
            .notificacion {
                top: 15px;
                right: 15px;
                left: 15px;
                max-width: none;
                font-size: 14px;
                padding: 14px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📋 SISTEMA DE INVENTARIO</div>
        <p class="subtitle">Escanea el código de barras para registrar movimientos</p>

        <div class="scanner-box" id="scannerBox">
            <span class="icon">📷</span>
            <p>Escanea el código de barras</p>
            <input type="text" id="scannerInput" placeholder="Código de barras..." autofocus>

            <div class="info-container">
                <span class="modo-badge entrada" id="modoTexto">📥 ENTRADA</span>
                <span class="contador-badge">🔄 Escaneos: <span id="contadorEscaneos">0</span></span>
            </div>

            <div class="btn-group">
                <button class="btn btn-limpiar" onclick="limpiar()">🗑️ Limpiar</button>
                <button class="btn btn-escanear" onclick="procesarEscaneo()">📡 Escanear</button>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-container">
            <div class="stat-card entrada">
                <div class="stat-label">📥 Entradas</div>
                <div class="stat-number" id="statEntradas">0</div>
                <div class="stat-sub">Productos ingresados</div>
            </div>
            <div class="stat-card salida">
                <div class="stat-label">📤 Salidas</div>
                <div class="stat-number" id="statSalidas">0</div>
                <div class="stat-sub">Productos retirados</div>
            </div>
        </div>

        <!-- Resultado producto -->
        <div class="resultado" id="resultado">
            <div class="producto-header">
                <div class="producto-nombre" id="productoNombre">-</div>
                <div class="producto-sku" id="productoSku">SKU: -</div>
            </div>
            <div class="producto-grid">
                <div class="producto-detalle"><span>Marca:</span> <span id="productoMarca">-</span></div>
                <div class="producto-detalle"><span>Modelo:</span> <span id="productoModelo">-</span></div>
                <div class="producto-detalle"><span>Categoría:</span> <span id="productoCategoria">-</span></div>
                <div class="producto-detalle"><span>Estado:</span> <span id="productoEstado">-</span></div>
            </div>
            <div class="stock verde" id="productoStock">📦 Stock: 0</div>
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
        let entradas = 0;
        let salidas = 0;

        const input = document.getElementById('scannerInput');
        const resultado = document.getElementById('resultado');
        const notificacion = document.getElementById('notificacion');
        const modoTexto = document.getElementById('modoTexto');
        const contadorEl = document.getElementById('contadorEscaneos');
        const statEntradas = document.getElementById('statEntradas');
        const statSalidas = document.getElementById('statSalidas');

        function mostrarNotificacion(mensaje, tipo) {
            notificacion.textContent = mensaje;
            notificacion.className = 'notificacion ' + tipo;
            setTimeout(() => {
                notificacion.className = 'notificacion';
            }, 3500);
        }

        function actualizarModo(contador) {
            // Calcular entradas y salidas basado en el contador
            entradas = Math.ceil(contador / 2);
            salidas = Math.floor(contador / 2);
            
            if (contador % 2 === 1) {
                modoActual = 'salida';
                modoTexto.textContent = '📤 SALIDA';
                modoTexto.className = 'modo-badge salida';
            } else {
                modoActual = 'entrada';
                modoTexto.textContent = '📥 ENTRADA';
                modoTexto.className = 'modo-badge entrada';
            }
            contadorEl.textContent = contador;
            statEntradas.textContent = entradas;
            statSalidas.textContent = salidas;
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
            stockEl.textContent = '📦 Stock: ' + stock;

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
                console.error(err);
                mostrarNotificacion('❌ Error al registrar', 'error');
            });
        }

        function procesarEscaneo() {
            let sku = input.value.trim();
            if (!sku) {
                mostrarNotificacion('⚠️ Ingresa un código de barras', 'advertencia');
                return;
            }

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

        // Detectar escaneo automático con timeout
        let timeoutId = null;
        input.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                if (this.value.trim() !== '') {
                    procesarEscaneo();
                }
            }, 300);
        });

        // Enfocar automáticamente al cargar
        document.addEventListener('DOMContentLoaded', function() {
            input.focus();
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
            this.classList.add('focused');
        });

        input.addEventListener('focus', function() {
            document.getElementById('scannerBox').classList.add('focused');
        });

        input.addEventListener('blur', function() {
            document.getElementById('scannerBox').classList.remove('focused');
        });

        function limpiar() {
            resultado.classList.remove('mostrar');
            input.value = '';
            input.focus();
            ultimoProducto = null;
            contadorEscaneos = 0;
            entradas = 0;
            salidas = 0;
            contadorEl.textContent = '0';
            statEntradas.textContent = '0';
            statSalidas.textContent = '0';
            modoTexto.textContent = '📥 ENTRADA';
            modoTexto.className = 'modo-badge entrada';
            mostrarNotificacion('🧹 Todo limpiado', 'info');
        }

        notificacion.addEventListener('click', function() {
            this.className = 'notificacion';
        });
    </script>
</body>
</html>