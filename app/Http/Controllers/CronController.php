<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Carteira;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use App\Models\Contrato;
use App\Models\Device;
use App\Models\Messagen;
use App\Models\Planilha;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
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

    public function mensagemEmMassa()
    {
        $now = Carbon::now('America/Sao_Paulo');

        $daysOfWeek = [
            0 => 'domingo',
            1 => 'segunda',
            2 => 'terça',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sábado',
        ];
        $dayOfWeek = $daysOfWeek[$now->dayOfWeek];
        $currentTime = $now->format('H:i:s');

        // Verifica horário disponível
        $exists = DB::table('available_slots')
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->exists();

        if (!$exists) {
            echo 'Fora de Data de Agendamento: ' . $currentTime;
            return;
        }

        // Pega campanhas ativas com contatos não enviados
        $campaigns = Campaign::where('status', 'play')
            ->with([
                'contactList' => function ($query) {
                    $query->wherePivot('send', false)->limit(1);
                },
                'devices' => function ($query) {
                    $query->where('status', 'open');
                }
            ])
            ->get();

        foreach ($campaigns as $campaign) {
            $contactList = $campaign->contactList->first();

            if (!$contactList || $contactList->phone == "") {
                continue;
            }

            // Define qual device será usado
            if ($campaign->devices->count() > 0) {
                $device = $campaign->devices->sortBy('updated_at')->first();
            } else {
                $device = Device::where('status', 'open')
                    ->orderBy('updated_at', 'asc')
                    ->first();
            }

            if (!$device) {
                echo "Nenhum device disponível para campanha {$campaign->id}.<br>";
                return;
            }

            if ($device->message_count_last_hour > 39) {
                echo "Device {$device->id} atingiu o limite de mensagens por hora.<br>";
                return;
            }

            // Preparar os dados para envio
            $imagem = asset($campaign->imagem->caminho);
            $texto = $campaign->texto ?? '';

            sleep(rand(1, 10)); // atraso aleatório

            $this->sendImage($device->session, $contactList->phone, $imagem, $texto);

            // Marca o contato como enviado
            $contactList->pivot->send = true;
            $contactList->pivot->save();

            // Atualiza o updated_at do device
            $device->touch();

            echo "Enviado para {$contactList->phone} via device {$device->id} <br>";

            // Enviou 1? Sai da função
            return;
        }

        echo "Nenhum contato para enviar agora.<br>";
    }



    public function sendImage($session, $phone, $urlImagem, $descricao = '')
    {
        $numero = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($numero, '55')) {
            $numero = substr($numero, 2);
        }

        $client = new \GuzzleHttp\Client();
        $url = "http://147.79.111.119:8080/message/sendMedia/{$session}";

        $headers = [
            'Content-Type' => 'application/json',
            'apikey' => env('TOKEN_EVOLUTION'),
        ];

        $body = json_encode([
            'number' => '55' . $numero,
            'mediatype' => 'image',
            'mimetype' => 'image/png',
            'caption' => $descricao,
            'media' => $urlImagem,
            'fileName' => 'imagem.png',
        ]);

        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers, $body);
            $response = $client->sendAsync($request)->wait();

            Log::info("Imagem enviada para 55{$numero}");
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error("Erro ao enviar imagem: " . $e->getMessage());
            return false;
        }
    }
}
