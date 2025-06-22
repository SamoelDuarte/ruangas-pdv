<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Messagen;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function evento(Request $request)
    {
        Log::info('Webhook recebido.', [
            'raw' => $request->getContent(),
            'headers' => $request->headers->all(),
            'method' => $request->method()
        ]);

        // Lê o conteúdo JSON
        $raw = file_get_contents("php://input");
         Log::info('Webhook recebido.', $raw);
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON inválido recebido no webhook.', ['body' => $raw]);
            return response()->json(['erro' => 'JSON inválido'], 400);
        }

        $numeroCompleto = $data['data']['key']['remoteJid'] ?? null;
        $mensagemTexto = $data['data']['message']['conversation'] ?? null;

        if (!$numeroCompleto || !$mensagemTexto) {
            Log::warning('Dados incompletos no webhook.', ['data' => $data]);
            return response()->json(['erro' => 'Dados incompletos'], 422);
        }

        // Lê o corpo cru da requisição
        $raw = $request->getContent();

        // Tenta decodificar o JSON
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON inválido recebido no webhook.', ['body' => $raw]);
            return response()->json(['erro' => 'JSON inválido'], 400);
        }

        $numeroCompleto = $data['data']['key']['remoteJid'] ?? null;
        $mensagemTexto = $data['data']['message']['conversation'] ?? null;

        if (!$numeroCompleto || !$mensagemTexto) {
            Log::warning('Dados incompletos no webhook.', ['data' => $data]);
            return response()->json(['erro' => 'Dados incompletos'], 422);
        }

        // Remove prefixo "55" e "@s.whatsapp.net"
        $numero = preg_replace('/[^0-9]/', '', $numeroCompleto);
        if (str_starts_with($numero, '55')) {
            $numero = substr($numero, 2);
        }

        // Codifica o número como está salvo no banco
        $numeroCodificado = base64_encode($numero);

        $cliente = Cliente::where('telefone', 'like', "%$numeroCodificado")->first();
        if (!$cliente) {
            Log::info("Cliente não encontrado para número: $numeroCodificado");
            return response()->json(['erro' => 'Cliente não encontrado'], 404);
        }

        $pedido = Pedido::where('cliente_id', $cliente->id)
            ->where('status_pedido_id', 8)
            ->orderByDesc('id')
            ->first();

        if (!$pedido) {
            Log::info("Pedido com status 8 não encontrado para cliente ID {$cliente->id}");
            return response()->json(['erro' => 'Pedido não encontrado'], 404);
        }

        $mensagem = new Messagen();
        $mensagem->pedido_id = $pedido->id;
        $mensagem->usuario_id = $pedido->entregador_id;
        $mensagem->messagem = $mensagemTexto;
        $mensagem->direcao = 'recebido';
        $mensagem->enviado = true;
        $mensagem->save();

        Log::info("Mensagem salva com sucesso", [
            'pedido_id' => $pedido->id,
            'cliente_id' => $cliente->id,
            'mensagem' => $mensagemTexto
        ]);

        return response()->json(['status' => 'ok']);
    }
}
