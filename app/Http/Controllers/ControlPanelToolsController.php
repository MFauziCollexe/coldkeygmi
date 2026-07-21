<?php

namespace App\Http\Controllers;

use Crenspire\Whatsapp\Facades\Whatsapp;
use Crenspire\Whatsapp\Exceptions\WhatsappException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class ControlPanelToolsController extends Controller
{
    public function sendWhatsAppTest(Request $request): JsonResponse
    {
        try {
            $recipient = '+6285645905565';
            $message = 'Test WhatsApp dari Control Panel Tools.';

            $response = Whatsapp::sendTextMessage($recipient, $message);

            return response()->json([
                'success' => true,
                'message' => 'Pesan WhatsApp test berhasil dikirim ke ' . $recipient . '.',
                'response' => $response,
            ]);
        } catch (WhatsappException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim WhatsApp: ' . $exception->getMessage(),
            ], 500);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim WhatsApp: ' . $exception->getMessage(),
            ], 500);
        }
    }

    public function testPostgresConnection(Request $request): JsonResponse
    {
        try {
            $config = Config::get('database.connections.pgsql');

            $host = $config['host'] ?? 'unknown';
            $port = $config['port'] ?? 'unknown';
            $database = $config['database'] ?? 'unknown';
            $username = $config['username'] ?? 'unknown';

            $start = microtime(true);
            $pdo = DB::connection('pgsql')->getPdo();
            $elapsed = round((microtime(true) - $start) * 1000);

            $serverVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

            return response()->json([
                'success' => true,
                'message' => 'Koneksi PostgreSQL berhasil!',
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'server_version' => $serverVersion,
                    'response_time_ms' => $elapsed,
                ],
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal koneksi ke PostgreSQL: ' . $exception->getMessage(),
            ], 500);
        }
    }
}
