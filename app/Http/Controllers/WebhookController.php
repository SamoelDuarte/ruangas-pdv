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
        // Codifica o número como está salvo no banco
        $numeroCodificado = base64_encode($numero);
        // dd($numero);
        // Busca cliente pelo telefone
        $cliente = Cliente::where('telefone', 'like', "%$numeroCodificado")->first();
        if (!$cliente) {
            exit;
        }


        // Busca pedido com status 8
        $pedido = Pedido::where('cliente_id', $cliente->id)
            ->where('status_pedido_id', 8)
            ->orderByDesc('id')
            ->first();

        // dd($pedido);
        // Salva a mensagem recebida
        $mensagem = new Messagen();
        $mensagem->pedido_id = $pedido->id;
        $mensagem->usuario_id = $pedido->entregador_id;
        $mensagem->messagem = $mensagem;
        $mensagem->direcao = 'recebido';
        $mensagem->enviado = true;
        $mensagem->save();




        return response()->json(['status' => 'ok']);
    }
}
