<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Messagen;
use App\Models\Pedido;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function evento(Request $request)
    {
        $raw = $request->getContent();
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['erro' => 'JSON inválido'], 400);
        }

        // Extrai número e mensagem
        $numeroCompleto = $data['sender'] ?? null;
        $mensagemTexto = $data['data']['message']['conversation'] ?? null;

        if (!$numeroCompleto || !$mensagemTexto) {
            return response()->json(['erro' => 'Dados incompletos'], 422);
        }

        // Remove prefixo "55" e "@s.whatsapp.net"
        $numero = preg_replace('/[^0-9]/', '', $numeroCompleto);
        if (str_starts_with($numero, '55')) {
            $numero = substr($numero, 2);
        }

        // Busca cliente
        $cliente = Cliente::where('telefone', 'like', "%$numero")->first();

        if (!$cliente) {
            return response()->json(['erro' => 'Cliente não encontrado'], 404);
        }

        // Busca pedido com status 8 desse cliente
        $pedido = Pedido::where('cliente_id', $cliente->id)
            ->where('status', 8)
            ->orderBy('id', 'desc')
            ->first();

        if (!$pedido) {
            return response()->json(['erro' => 'Pedido não encontrado'], 404);
        }

        // Cria e salva a mensagem
        $mensagem = new Messagen();
        $mensagem->pedido_id = $pedido->id;
        $mensagem->cliente_id = $cliente->id;
        $mensagem->messagem = $mensagemTexto;
        $mensagem->direcao = 'recebido';
        $mensagem->enviado = true; // já foi recebido
        $mensagem->save();


        return response()->json(['status' => 'mensagem salva com sucesso']);
    }
}
