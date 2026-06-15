<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 10px;
        }
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 9px;
            margin: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding: 8px;
            background-color: #2E7D32;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
        }
        .header p {
            margin: 3px 0 0;
            font-size: 9px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #2E7D32;
            color: white;
            padding: 6px;
            text-align: left;
            font-size: 8px;
            border: 1px solid #1B5E20;
        }
        td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 8px;
            word-wrap: break-word;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 7px;
            color: #666;
        }
        .total {
            margin-top: 10px;
            text-align: right;
            font-size: 9px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
        <p>Generado: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total de guías: {{ $total }}
    </div>

    <div class="footer">
        Este reporte fue generado automáticamente por el Sistema de Inventario.
    </div>
</body>
</html>