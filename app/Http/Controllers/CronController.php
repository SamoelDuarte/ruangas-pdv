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
        $mensagens = Messagen::where('enviado', false)
            ->with(['pedido.cliente', 'device', 'entregador'])
            ->limit(20)
            ->get();
        dd( $mensagens);
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

                $mensagemFormatada = 'Mensagem Entregador ('.$nomeEntregador.')\n$textoOriginal';

                $headers = [
                    'Content-Type' => 'application/json',
                    'apikey' => env('TOKEN_EVOLUTION') // Substitua pela chave real se necessário
                ];

                $body = json_encode([
                    'number' => $numero,
                    'text' => $mensagemFormatada
                ]);

                $url = "http://147.79.111.119:8080/message/sendText/{$mensagem->device->uuid}";

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
