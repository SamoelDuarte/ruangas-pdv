<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
   public function evento(Request $request)
{
    // $payload = $request->all();

    // // Verifica se veio o evento correto
    // if (!isset($payload['event']) || $payload['event'] !== 'messages.upsert') {
    //     return response()->json(['erro' => 'Evento inválido'], 400);
    // }

    // $fullJid = $payload['sender'] ?? null;
    // $mensagemTexto = $payload['data']['message']['conversation'] ?? null;

    // if (!$fullJid || !$mensagemTexto) {
    //     return response()->json(['erro' => 'Dados incompletos'], 400);
    // }

    // // Extrai número (remove @s.whatsapp.net e o 55 do início)
    // $numero = str_replace(['@s.whatsapp.net', '55'], '', $fullJid);

    // // Busca o pedido com status 8 cujo cliente tem esse telefone
    // $pedido = Pedido::where('status_pedido_id', 8)
    //     ->whereHas('cliente', function ($q) use ($numero) {
    //         $q->where('telefone', $numero);
    //     })
    //     ->latest() // caso haja mais de um, pega o mais recente
    //     ->first();
    //     dd($pedido);
    // if (!$pedido) {
    //     return response()->json(['erro' => 'Pedido não encontrado para esse cliente'], 404);
    // }

    // // Cria a mensagem recebida
    // $mensagem = \App\Models\Messagen::create([
    //     'pedido_id'   => $pedido->id,
    //     'messagem'    => $mensagemTexto,
    //     'direcao'     => 'recebido',
    //     'enviado'     => true, // já foi recebida
    //     'device_id'   => null,
    //     'usuario_id'  => null,
    // ]);

    // return response()->json([
    //     'status' => 'Mensagem registrada',
    //     'mensagem' => $mensagem
    // ]);
}

}
