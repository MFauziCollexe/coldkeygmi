<?php

namespace App\Http\Controllers;

use Crenspire\Whatsapp\Facades\Whatsapp;
use Crenspire\Whatsapp\Exceptions\WhatsappException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
}
