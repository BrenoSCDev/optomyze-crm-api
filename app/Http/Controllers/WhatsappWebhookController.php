<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Log para depuração
        Log::info('Webhook recebido Evolution', $payload);

        // Se o payload for vazio → erro no parse
        if (empty($payload)) {
            return response()->json(['message' => 'Empty payload'], 200);
        }

        // O Evolution manda um array
        $body = $payload[0]['body'] ?? null;

        if (!$body || !isset($body['data'])) {
            return response()->json(['message' => 'Payload without data'], 200);
        }

        $data = $body['data'];

        $messageId = $data['key']['id'] ?? null;
        $text      = $data['message']['conversation'] ?? null;

        \App\Models\WhatsappMessage::updateOrCreate(
            ['message_id' => $messageId],
            [
                'from'       => $data['key']['remoteJid'] ?? null,
                'to'         => $body['sender'] ?? null,
                'message'    => $text,
                'direction'  => ($data['key']['fromMe'] ?? false) ? 'outbound' : 'inbound',
                'status'     => $data['status'] ?? null,
                'timestamp'  => isset($data['messageTimestamp'])
                    ? \Carbon\Carbon::createFromTimestamp($data['messageTimestamp'])
                    : now(),
                'raw_payload'=> $data,
            ]
        );

        return response()->json(['message' => 'Message stored']);
    }

    public function get()
    {
        return response()->json([
            'data' => WhatsappMessage::get()
        ]);
    }
}
