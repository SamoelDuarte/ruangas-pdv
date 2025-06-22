<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
        public function evento(Request $request)
        {
            // Lê o corpo cru da requisição
            $raw = $request->getContent();

            // Tenta decodificar o JSON
            $data = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['erro' => 'JSON inválido'], 400);
            }

            // Agora você pode acessar $data['sender'], $data['data']['message']['conversation'], etc.
            $numero = $data['sender'] ?? null;
            $mensagem = $data['data']['message']['conversation'] ?? null;
            
            dd($mensagem);

            return response()->json(['status' => 'ok']);
        }
}
