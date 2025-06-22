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
        $mensagens = Messagen::where('enviado', false)
            ->where('direcao', 'enviado')
            ->whereHas('pedido', function ($query) {
                $query->where('status_pedido_id', 8);
            })
            ->with(['pedido.cliente', 'device', 'entregador'])
            ->limit(20)
            ->get();


        foreach ($mensagens as $mensagem) {
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

            $enviado = $this->enviarMensagem($mensagem);

            if ($enviado) {
                $mensagem->enviado = true;
                $mensagem->save();
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function enviarMensagem(Messagen $mensagem)
    {
        $client = new Client();

        $numero = $mensagem->pedido->cliente->telefone;
        $nomeEntregador = $mensagem->entregador->nome;
        $textoOriginal = $mensagem->messagem;

        // Monta a mensagem formatada com quebras de linha
        $mensagemFormatada = "Mensagem Entregador ({$nomeEntregador})\n{$textoOriginal}";

        $headers = [
            'Content-Type' => 'application/json',
            'apikey' => env('TOKEN_EVOLUTION'),
        ];

        $body = json_encode([
            'number' => '55' . $numero,
            'text' => $mensagemFormatada,
        ]);

        $url = "http://147.79.111.119:8080/message/sendText/{$mensagem->device->session}";

        $request = new Request('POST', $url, $headers, $body);

        try {
            $response = $client->sendAsync($request)->wait();
            $statusCode = $response->getStatusCode();
            $bodyResponse = $response->getBody()->getContents();

            Log::info("Mensagem ID {$mensagem->id} enviada com status {$statusCode}: {$bodyResponse}");

            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao enviar mensagem ID {$mensagem->id}: " . $e->getMessage());
            return false;
        }
    }
}
