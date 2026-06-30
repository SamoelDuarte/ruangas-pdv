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
use Illuminate\Support\Facades\Cache;
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

        $url = rtrim((string) env('APP_URL_ZAP'), '/') . "/message/sendText/{$mensagem->device->session}";

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
            // Pega todos os contatos não enviados desta campanha
            $contactList = $campaign->contactList()
                ->wherePivot('send', false)
                ->orderBy('contact_list.id', 'asc')
                ->get();

            if ($contactList->isEmpty()) {
                continue;
            }

            // Filtra apenas contatos com telefone
            $contactList = $contactList->filter(function($contact) {
                return !empty($contact->phone);
            });

            if ($contactList->isEmpty()) {
                continue;
            }

            // Pega todos os devices disponíveis da campanha que estão no horário
            $availableDevices = [];

            if ($campaign->devices->count() > 0) {
                foreach ($campaign->devices as $device) {
                    if ($device->status !== 'open') {
                        continue;
                    }

                    if ($device->message_count_last_hour > 39) {
                        echo "Device {$device->id} atingiu o limite de mensagens por hora.<br>";
                        continue;
                    }

                    // Converte os intervalos para segundos
                    $startInterval = ($device->start_minutes * 60) + $device->start_seconds;
                    $endInterval = ($device->end_minutes * 60) + $device->end_seconds;

                    // Gera um intervalo aleatório entre start e end
                    $randomInterval = rand($startInterval, $endInterval);
                    
                    // Verifica quanto tempo passou desde o último envio
                    $lastUpdate = Carbon::parse($device->updated_at);
                    $now = Carbon::now();
                    $diffInSeconds = $now->diffInSeconds($lastUpdate);

                    // Se já passou o tempo do intervalo aleatório, adiciona ao array
                    if ($diffInSeconds >= $randomInterval) {
                        $availableDevices[] = $device;
                        echo "Device {$device->id} disponível para envio (última atualização: {$diffInSeconds}s, intervalo sorteado: {$randomInterval}s entre {$startInterval}s e {$endInterval}s).<br>";
                    } else {
                        echo "Device {$device->id} ainda não atingiu o intervalo mínimo (última atualização: {$diffInSeconds}s, precisa esperar: {$randomInterval}s, entre {$startInterval}s e {$endInterval}s).<br>";
                    }
                }
            }

            // Se não encontrou nenhum device disponível, pula para próxima campanha
            if (empty($availableDevices)) {
                echo "Nenhum device disponível dentro do intervalo de tempo para a campanha {$campaign->id}.<br>";
                continue;
            }

            // Para cada device disponível, pega um contato diferente e envia
            foreach ($availableDevices as $index => $device) {
                // Verifica se ainda tem contatos disponíveis
                if ($index >= $contactList->count()) {
                    break; // Sai do loop se não houver mais contatos
                }

                // Se o device não estiver aquecido, tenta fazer a troca de mensagens com outro device.
                if (!$this->isDeviceWarmed($device)) {
                    $peerDevice = $this->findWarmupPeerDevice($device, $campaign->devices->where('id', '!=', $device->id));

                    if ($peerDevice) {
                        $warmupOk = $this->performWarmupExchange($device, $peerDevice);

                        if (!$warmupOk) {
                            echo "Falha ao aquecer device {$device->id} com peer {$peerDevice->id}.<br>";
                            continue;
                        }

                        echo "Device {$device->id} aquecido com sucesso usando peer {$peerDevice->id}.<br>";
                    } else {
                        echo "Nenhum peer disponível para aquecer device {$device->id}.<br>";
                        continue;
                    }
                }

                // Pega o próximo contato da lista
                $contact = $contactList[$index];

                // Preparar os dados para envio
                $imagem = asset($campaign->imagem->caminho);
                $texto = $campaign->texto ?? '';

                $sendOk = $this->sendImage($device->session, $contact->phone, $imagem, $texto);

                if ($sendOk) {
                    // Atualiza o updated_at do device apenas quando a API confirmou aceitação
                    $device->touch();

                    // Marca contato como enviado apenas em sucesso real
                    $contact->pivot->send = true;
                    $contact->pivot->save();

                    echo "Enviado para {$contact->phone} via device {$device->id} <br>";
                } else {
                    echo "Falha ao enviar para {$contact->phone} via device {$device->id} <br>";
                }
            }

            // Já processou este contato, vai para a próxima campanha
            continue;
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
        $url = rtrim((string) env('APP_URL_ZAP'), '/') . "/message/sendMedia/{$session}";

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
            // Log da requisição
            Log::info("Enviando mensagem para Evolution API", [
                'numero' => '55' . $numero,
                'session' => $session,
                'url' => $url
            ]);

            $request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers, $body);
            $response = $client->sendAsync($request)->wait();
            
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode((string) $response->getBody(), true);

            // Log detalhado da resposta
            Log::info("Resposta da Evolution API", [
                'numero' => '55' . $numero,
                'status' => $statusCode,
                'response' => $responseBody,
                'session' => $session
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("Mensagem enviada com sucesso", [
                    'numero' => '55' . $numero,
                    'session' => $session,
                    'responseBody' => $responseBody
                ]);
                return true;
            } else {
                Log::warning("Resposta inesperada da Evolution API", [
                    'numero' => '55' . $numero,
                    'status' => $statusCode,
                    'response' => $responseBody,
                    'session' => $session
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Erro ao enviar mensagem para Evolution API", [
                'numero' => '55' . $numero,
                'session' => $session,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function isDeviceWarmed(Device $device)
    {
        return Cache::has("device_warmed:{$device->id}");
    }

    private function markDeviceWarmed(Device $device)
    {
        Cache::put("device_warmed:{$device->id}", true, now()->addMinutes(60));
    }

    private function findWarmupPeerDevice(Device $device, $devices)
    {
        $peer = $devices->firstWhere('status', 'open');

        if ($peer && $peer->jid) {
            return $peer;
        }

        return Device::where('status', 'open')
            ->whereNotNull('jid')
            ->where('id', '!=', $device->id)
            ->first();
    }

    private function performWarmupExchange(Device $sender, Device $recipient)
    {
        if (empty($sender->jid) || empty($recipient->jid)) {
            Log::warning("Warmup ignorado: falta JID em sender {$sender->id} ou recipient {$recipient->id}");
            return false;
        }

        $warmupMessages = [
            "Olá, estou verificando a conexão antes de começar os envios. Pode responder para validar o chat?",
            "Oi, preciso confirmar que temos uma conversa ativa entre dispositivos antes de enviar as mensagens de campanha.",
            "Teste de conexão: vamos trocar duas mensagens para aquecer o envio e evitar bloqueios."
        ];

        $warmupResponses = [
            "Recebido, validação feita. Estou pronto para enviar.",
            "Tudo certo, a conexão foi aceita e posso continuar.",
            "Conversa estabelecida com sucesso. Pode prosseguir com os envios."
        ];

        $firstText = $warmupMessages[array_rand($warmupMessages)];
        $secondText = $warmupResponses[array_rand($warmupResponses)];

        $sentFirst = $this->sendDeviceText($sender->session, $recipient->jid, $firstText);
        if (!$sentFirst) {
            return false;
        }

        $sentSecond = $this->sendDeviceText($recipient->session, $sender->jid, $secondText);
        if (!$sentSecond) {
            return false;
        }

        $this->markDeviceWarmed($sender);
        $this->markDeviceWarmed($recipient);

        return true;
    }

    private function sendDeviceText($session, $numberOrJid, $text)
    {
        $recipient = trim($numberOrJid);
        if (strpos($recipient, '@') === false) {
            $recipient = '55' . preg_replace('/[^0-9]/', '', $recipient);
        }

        $client = new \GuzzleHttp\Client();
        $url = rtrim((string) env('APP_URL_ZAP'), '/') . "/message/sendText/{$session}";

        $headers = [
            'Content-Type' => 'application/json',
            'apikey' => env('TOKEN_EVOLUTION'),
        ];

        $body = json_encode([
            'number' => $recipient,
            'text' => $text,
        ]);

        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers, $body);
            $response = $client->sendAsync($request)->wait();
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("Warmup enviado com sucesso para {$recipient} via session {$session}");
                return true;
            }

            Log::warning("Warmup falhou com status inesperado", ['session' => $session, 'recipient' => $recipient, 'status' => $statusCode]);
            return false;
        } catch (\Exception $e) {
            Log::error("Erro ao enviar warmup para {$recipient}", ['session' => $session, 'erro' => $e->getMessage()]);
            return false;
        }
    }

    public function verificarMensagensPendentes()
    {
        try {
            // Busca mensagens pendentes com mais de 2 minutos
            $mensagensPendentes = \App\Models\MessageQueue::where('status', 'pending')
                ->where('created_at', '<=', now()->subMinutes(2))
                ->get()
                ->groupBy('device_session'); // Agrupa por dispositivo

            if ($mensagensPendentes->isEmpty()) {
                return response()->json(['status' => 'ok', 'message' => 'Nenhuma mensagem pendente']);
            }

            // Números para notificar
            $numerosNotificar = ['5511992526232','5511949745807'];

            // Para cada dispositivo
            foreach ($mensagensPendentes as $deviceSession => $mensagens) {
                // Agrupa mensagens por remetente para cada dispositivo
                $mensagensPorRemetente = $mensagens->groupBy('sender_number');
                
                foreach ($mensagensPorRemetente as $senderNumber => $mensagensDoRemetente) {
                    // Pega a última mensagem deste remetente
                    $ultimaMensagem = $mensagensDoRemetente->sortByDesc('created_at')->first();
                    
                    // Busca informações do dispositivo
                    $deviceInfo = Device::where('session', $deviceSession)->first();
                    $deviceName = $deviceInfo ? ($deviceInfo->name ?: "Dispositivo #" . $deviceInfo->id) : "Dispositivo Desconhecido";

                    // Formata a mensagem
                    $mensagemFormatada = "🚨 *NOVA MENSAGEM PENDENTE* 🚨\n\n";
                    $mensagemFormatada .= "📱 *Número do Cliente:* " . $senderNumber . "\n";
                    $mensagemFormatada .= "📲 *Dispositivo:* " . $deviceName . "\n";
                    $mensagemFormatada .= "💬 *Última Mensagem:* " . $ultimaMensagem->message . "\n";
                    $mensagemFormatada .= "⏰ *Recebida às:* " . $ultimaMensagem->created_at->format('H:i:s') . "\n";
                    $mensagemFormatada .= "📝 *Total de mensagens:* " . $mensagensDoRemetente->count() . "\n\n";
                    
                    // Tenta usar o mesmo dispositivo que recebeu a mensagem
                    $device = Device::where('session', $deviceSession)
                                  ->where('status', 'open')
                                  ->first();

                    if (!$device) {
                        // Se o dispositivo original não estiver disponível, tenta outro
                        $device = Device::where('status', 'open')->first();
                    }

                    if ($device) {
                        // Envia para cada número de notificação
                        $notificacoesEnviadas = true;
                        foreach ($numerosNotificar as $numero) {
                            if (!$this->enviarNotificacao($device->session, $numero, $mensagemFormatada)) {
                                $notificacoesEnviadas = false;
                                break;
                            }
                        }

                        // Se todas as notificações foram enviadas com sucesso, apaga as mensagens
                        if ($notificacoesEnviadas) {
                            $mensagensDoRemetente->each(function($mensagem) {
                                $mensagem->delete();
                            });
                            Log::info("Mensagens pendentes deletadas após notificação", [
                                'sender' => $senderNumber,
                                'device' => $deviceSession,
                                'quantidade' => $mensagensDoRemetente->count()
                            ]);
                        }
                    } else {
                        Log::error("Nenhum dispositivo ativo para enviar notificação de pendências");
                    }
                }
            }

            return response()->json([
                'status' => 'ok',
                'message' => 'Notificações enviadas',
                'total_pendentes' => $mensagensPendentes->flatten()->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao verificar mensagens pendentes: " . $e->getMessage());
            return response()->json(['status' => 'erro', 'message' => $e->getMessage()]);
        }
    }

    private function enviarNotificacao($session, $numero, $mensagem)
    {
        $client = new Client();
        $url = rtrim((string) env('APP_URL_ZAP'), '/') . "/message/sendText/{$session}";

        $headers = [
            'Content-Type' => 'application/json',
            'apikey' => env('TOKEN_EVOLUTION'),
        ];

        $body = json_encode([
            'number' => $numero,
            'text' => $mensagem
        ]);

        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers, $body);
            $response = $client->sendAsync($request)->wait();

            Log::info("Notificação de pendências enviada para {$numero}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação para {$numero}: " . $e->getMessage());
            return false;
        }
    }

    public function atualizarWebhooksDispositivos()
    {
        // Pega todos os dispositivos com sessão ativa
        $devices = Device::whereNotNull('session')
                         ->where('status', 'open')
                         ->get();

        $client = new Client();
        $sucessos = 0;
        $erros = 0;

        foreach ($devices as $device) {
            try {
                $headers = [
                    'Content-Type' => 'application/json',
                    'apikey' => env('TOKEN_EVOLUTION'),
                ];

                $body = json_encode([
                    'webhook' => [
                        'enabled' => true,
                        'url' => 'https://sistema.ruangas.com.br/webhook',
                        'headers' => [
                            'authorization' => 'Bearer ' . env('TOKEN_EVOLUTION'),
                            'Content-Type' => 'application/json'
                        ],
                        'byEvents' => false,
                        'base64' => false,
                        'events' => [
                            'MESSAGES_UPSERT'
                        ]
                    ]
                ]);

                $url = rtrim((string) env('APP_URL_ZAP'), '/') . "/webhook/set/{$device->session}";
                $request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers, $body);
                $response = $client->sendAsync($request)->wait();

                if ($response->getStatusCode() === 200) {
                    Log::info("Webhook atualizado com sucesso para dispositivo {$device->id} (sessão: {$device->session})");
                    $sucessos++;
                } else {
                    Log::warning("Resposta inesperada ao atualizar webhook do dispositivo {$device->id}: " . $response->getStatusCode());
                    $erros++;
                }

            } catch (\Exception $e) {
                Log::error("Erro ao atualizar webhook do dispositivo {$device->id}: " . $e->getMessage());
                $erros++;
            }
        }

        $mensagem = "Webhooks atualizados: {$sucessos} sucessos, {$erros} erros";
        Log::info($mensagem);
        
        return response()->json([
            'status' => 'concluido',
            'sucessos' => $sucessos,
            'erros' => $erros,
            'total_dispositivos' => $devices->count(),
            'mensagem' => $mensagem
        ]);
    }

}