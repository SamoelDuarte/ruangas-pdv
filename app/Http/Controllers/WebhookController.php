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
        $numeroCompleto = $data['sender'] ?? null;
        $mensagem = $data['data']['message']['conversation'] ?? null;

        // Remove prefixo "55" e "@s.whatsapp.net"
        $numero = preg_replace('/[^0-9]/', '', $numeroCompleto);
        if (str_starts_with($numero, '55')) {
            $numero = substr($numero, 2);
        }
        // Busca cliente pelo telefone
        $cliente = Cliente::where('telefone', 'like', "%$numero")->first();

        if (!$cliente) {
            return response()->json(['erro' => 'Cliente não encontrado'], 404);
        }

        // Busca pedido com status 8
        $pedido = Pedido::where('cliente_id', $cliente->id)
            ->where('status', 8)
            ->orderByDesc('id')
            ->first();

        if (!$pedido) {
            return response()->json(['erro' => 'Pedido com status 8 não encontrado'], 404);
        }
        dd($pedido);

        return response()->json(['status' => 'ok']);
    }
}
