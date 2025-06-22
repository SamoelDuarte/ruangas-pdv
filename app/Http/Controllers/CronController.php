<?php

namespace App\Http\Controllers;

use App\Models\Carteira;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use App\Models\Contrato;
use App\Models\Messagen;
use App\Models\Planilha;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    public function enviarPendentes()
    {
        // Busca até 20 mensagens não enviadas
        $mensagens = Messagen::where('enviado', false)->where('direcao', 'enviado')
            ->with(['pedido.cliente', 'device', 'entregador'])
            ->limit(20)
            ->get();
        // dd( $mensagens);
        $client = new Client();

        foreach ($mensagens as $mensagem) {
            try {
                // Validação mínima
                if (
                    !$mensagem->pedido ||
                    !$mensagem->pedido->cliente ||
                    !$mensagem->device ||
                    !$mensagem->entregador
                ) {
                    Log::warning("Mensagem {$mensagem->id} ignorada por falta de dados relacionados.");
                    continue;
                }

                $numero = $mensagem->pedido->cliente->telefone;
                $nomeEntregador = $mensagem->entregador->nome;
                $textoOriginal = $mensagem->messagem;
              $text = "Olá! 👋 É sempre um prazer ter você com a gente! 😊\n\n"
      . "Notamos que você deixou alguns produtos no carrinho e não queremos que você perca essas ofertas incríveis! 🛒\n\n"
      . "📋 *Resumo do seu carrinho:*\n"
      . "\n"
      . "💰 *Total:* \n"
      . "🛍️ Para finalizar sua compra, é só clicar no link abaixo:\n"
      . "🔗 \n"
      . "Fácil, rápido e prático! 🚀 Não perca essa chance de garantir seus produtos favoritos! 😊";


                $mensagemFormatada = 'Mensagem Entregador (' . $nomeEntregador . ') \n ' . $textoOriginal . '';

                $headers = [
                    'Content-Type' => 'application/json',
                    'apikey' => env('TOKEN_EVOLUTION') // Substitua pela chave real se necessário
                ];

                $body = json_encode([
                    'number' => '55' . $numero,
                    'text' => $text
                ]);

                $url = "http://147.79.111.119:8080/message/sendText/{$mensagem->device->session}";

                $request = new Request('POST', $url, $headers, $body);

                $response = $client->sendAsync($request)->wait();

                $statusCode = $response->getStatusCode();
                $bodyResponse = $response->getBody()->getContents();

                Log::info("Mensagem ID {$mensagem->id} enviada com status {$statusCode}: {$bodyResponse}");

                if ($statusCode === 200) {
                    $mensagem->enviado = true;
                    $mensagem->save();
                }
            } catch (\Exception $e) {
                Log::error("Erro ao enviar mensagem ID {$mensagem->id}: " . $e->getMessage());
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
