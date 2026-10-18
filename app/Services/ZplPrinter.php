<?php

namespace App\Services;

class ZplPrinter
{
    /**
     * Envía un bloque ZPL a la impresora según el driver configurado.
     *
     * @return array{ok: bool, message: string}
     */
    public static function print(string $zpl, ?string $filename = null): array
    {
        $driver = self::driver();

        return match ($driver) {
            'network' => self::printNetwork($zpl),
            'windows' => self::printWindows($zpl, $filename),
            'cups' => self::printCups($zpl, $filename),
            default => [
                'ok' => false,
                'message' => "Driver ZPL desconocido: '{$driver}'. Usa cups, network o windows.",
            ],
        };
    }

    private static function driver(): string
    {
        $driver = config('services.zpl.driver');

        if (filled($driver)) {
            return strtolower((string) $driver);
        }

        return PHP_OS_FAMILY === 'Windows' ? 'windows' : 'cups';
    }

    /**
     * Impresión raw por cola CUPS (Linux/Mac). Impresora USB.
     */
    private static function printCups(string $zpl, ?string $filename): array
    {
        $file = self::writeTempFile($zpl, $filename);
        $queue = self::config('queue', 'Zebra');

        $command = sprintf(
            'lp -d %s -o raw %s 2>&1',
            escapeshellarg($queue),
            escapeshellarg($file)
        );

        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            return [
                'ok' => true,
                'message' => "✅ Etiqueta enviada a la impresora '{$queue}'.",
            ];
        }

        return [
            'ok' => false,
            'message' => '❌ No se pudo imprimir por CUPS (lp). ' . trim(implode(' ', $output)),
        ];
    }

    /**
     * Impresión por red: socket TCP al puerto raw (normalmente 9100).
     */
    private static function printNetwork(string $zpl): array
    {
        $host = self::config('host', '192.168.1.100');
        $port = (int) config('services.zpl.port', 9100);

        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if (! $socket) {
            return [
                'ok' => false,
                'message' => "❌ No se pudo conectar a la impresora {$host}:{$port}. {$errstr} ({$errno})",
            ];
        }

        fwrite($socket, $zpl);
        fclose($socket);

        return [
            'ok' => true,
            'message' => "✅ Etiqueta enviada a la impresora de red {$host}:{$port}.",
        ];
    }

    /**
     * Impresión en Windows mediante Zebra Setup Utilities (PrnUtils.exe).
     */
    private static function printWindows(string $zpl, ?string $filename): array
    {
        $file = self::writeTempFile($zpl, $filename);
        $prnUtils = config('services.zpl.windows_prn_utils');
        $port = self::config('windows_port', 'USB001');

        $command = sprintf(
            'start "" %s /direct /p %s /f %s',
            escapeshellarg($prnUtils),
            escapeshellarg($port),
            escapeshellarg($file)
        );

        try {
            pclose(popen("start /B {$command}", 'r'));

            return [
                'ok' => true,
                'message' => '✅ Direct Communication abierto. Presiona Ctrl+Enter para imprimir.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => '❌ Error al abrir Direct Communication: ' . $e->getMessage(),
            ];
        }
    }

    private static function writeTempFile(string $zpl, ?string $filename): string
    {
        $directory = storage_path('app/temp');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $directory . DIRECTORY_SEPARATOR . ($filename ?: 'etiqueta.zpl');

        file_put_contents($file, $zpl);

        return $file;
    }

    private static function config(string $key, string $default): string
    {
        return (string) config("services.zpl.{$key}", $default);
    }
}
