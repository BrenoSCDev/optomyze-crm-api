<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappMessage;
use Carbon\Carbon;

class WhatsappWebhookController extends Controller
{
public function handle(Request $request)
    {
        // Evolution envia um ARRAY de objetos
        $payload = $request->all();

        if (!is_array($payload) || !isset($payload[0]['body']['data'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $data = $payload[0]['body']['data'];

        // Extrair informações da mensagem
        $messageId   = $data['key']['id'] ?? null;
        $from        = $data['key']['remoteJid'] ?? null;
        $fromMe      = $data['key']['fromMe'] ?? false;
        $sender      = $payload[0]['body']['sender'] ?? null;
        $to          = $fromMe ? ($sender ?? null) : null; // opcional
        $text        = $data['message']['conversation'] ?? null;
        $status      = $data['status'] ?? null;
        $timestamp   = $data['messageTimestamp'] ?? null;

        $whatsappMessage = WhatsappMessage::updateOrCreate(
            ['message_id' => $messageId],
            [
                'from'       => $from,
                'to'         => $to,
                'message'    => $text,
                'direction'  => $fromMe ? 'outbound' : 'inbound',
                'status'     => $status,
                'timestamp'  => $timestamp 
                                ? Carbon::createFromTimestamp($timestamp) 
                                : now(),
                'raw_payload'=> $data,
            ]
        );

        return response()->json([
            'message' => 'Message stored successfully',
            'data'    => $whatsappMessage
        ]);
    }

    public function get()
    {
        return response()->json([
            'data' => WhatsappMessage::get()
        ]);
    }
}
