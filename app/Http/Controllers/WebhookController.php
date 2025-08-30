<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Device;
use App\Models\MessageQueue;
use App\Models\Messagen;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function evento(Request $request)
    {
        try {
            // Lê o corpo da requisição
            $raw = $request->getContent();
            $data = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("JSON inválido recebido", ['payload' => $raw]);
                return response()->json(['erro' => 'JSON inválido'], 400);
            }

            // Verifica se é uma mensagem enviada por nós
            $fromMe = $data['data']['key']['fromMe'] ?? false;
            $numeroCompleto = $data['data']['key']['remoteJid'] ?? null;
            $mensagemTexto = $data['data']['message']['conversation'] ?? 
                           ($data['data']['message']['extendedTextMessage']['text'] ?? null);

            if (!$numeroCompleto) {
                Log::error("Número não encontrado no webhook", ['data' => $data]);
                return response()->json(['erro' => 'Número não encontrado'], 422);
            }

            // Limpa e formata o número
            $numero = preg_replace('/[^0-9]/', '', $numeroCompleto);
            if (str_starts_with($numero, '55')) {
                $numero = substr($numero, 2);
            }

            // Se a mensagem for enviada por nós, apenas registramos na fila
            if ($fromMe) {
                // Quando enviamos uma mensagem, apenas deletar as mensagens pendentes deste número
                MessageQueue::where('sender_number', "55{$numero}")
                    ->where('status', 'pending')
                    ->delete();

                Log::info("Mensagens pendentes deletadas para {$numero}");
                return response()->json(['status' => 'Mensagens pendentes removidas']);
            }

            // Para mensagens recebidas, processa normalmente
            if ($mensagemTexto) {
                // Codifica o número como está salvo no banco
                $numeroCodificado = base64_encode($numero);

                $cliente = Cliente::where('telefone', 'like', "%$numeroCodificado")->first();
                if ($cliente) {
                    $pedido = Pedido::where('cliente_id', $cliente->id)
                        ->where('status_pedido_id', 8)
                        ->orderByDesc('id')
                        ->first();

                    if ($pedido) {
                        // Salva a mensagem relacionada ao pedido
                        $mensagem = new Messagen();
                        $mensagem->pedido_id = $pedido->id;
                        $mensagem->usuario_id = $pedido->entregador_id;
                        $mensagem->messagem = $mensagemTexto;
                        $mensagem->direcao = 'recebido';
                        $mensagem->enviado = true;
                        $mensagem->save();

                        // Adiciona à fila de mensagens
                        $messageQueue = new MessageQueue([
                            'device_session' => Device::where('status', 'open')->first()?->session,
                            'sender_number' => "55{$numero}",
                            'message' => $mensagemTexto,
                            'message_type' => 'text',
                            'is_from_me' => false,
                            'status' => 'received'
                        ]);
                        $messageQueue->save();

                        Log::info("Mensagem processada para o pedido {$pedido->id} do cliente {$cliente->id}");
                        return response()->json(['status' => 'Mensagem processada com sucesso']);
                    }
                }
            }

            // Se chegou até aqui, registra a mensagem na fila mesmo sem encontrar cliente/pedido
            $messageQueue = new MessageQueue([
                'device_session' => Device::where('status', 'open')->first()?->session,
                'sender_number' => "55{$numero}",
                'message' => $mensagemTexto ?? '',
                'message_type' => 'text',
                'is_from_me' => false,
                'status' => 'pending'
            ]);
            $messageQueue->save();

            Log::info("Mensagem registrada sem vinculação para o número {$numero}");
            return response()->json(['status' => 'Mensagem registrada sem vinculação']);

        } catch (\Exception $e) {
            Log::error("Erro ao processar webhook: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['erro' => 'Erro interno', 'mensagem' => $e->getMessage()], 500);
        }
    }

    public function envent(Request $request)
    {
        try {
            // Lê o corpo da requisição
            $payload = $request->getContent();
            $data = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("JSON inválido recebido no webhook", ['payload' => $payload]);
                return response()->json(['error' => 'JSON inválido'], 400);
            }

            // Extrai informações básicas
            $instance = $data['instance'] ?? null;
            $event = $data['event'] ?? null;

            if (!$instance || !$event) {
                Log::error("Dados incompletos no webhook", ['data' => $data]);
                return response()->json(['error' => 'Dados incompletos'], 422);
            }

            Log::info("Evento recebido", [
                'instance' => $instance,
                'event' => $event,
                'data' => $data
            ]);

            $this->processarEventoMensagem($instance, $data['data'] ?? []);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error("Erro ao processar webhook: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Erro interno'], 500);
        }
    }


    private function processarEventoMensagem($instance, $data)
    {
        try {
            Log::info("Evento de mensagem processado", [
                'instance' => $instance,
                'data' => $data
            ]);

            // Extrai os dados relevantes da mensagem
            $remoteJid = $data['key']['remoteJid'] ?? null;
            $messageType = $data['messageType'] ?? 'text';
            $messageContent = '';
            $isFromMe = $data['key']['fromMe'] ?? false;

            // Determina o conteúdo da mensagem com base no tipo
            if (isset($data['message'])) {
                if (isset($data['message']['conversation'])) {
                    $messageContent = $data['message']['conversation'];
                } elseif (isset($data['message']['extendedTextMessage'])) {
                    $messageContent = $data['message']['extendedTextMessage']['text'];
                }
                // Adicione outros tipos de mensagem conforme necessário
            }

            if ($remoteJid && $messageContent) {
                // Cria uma nova mensagem na fila
                $queueMessage = new MessageQueue([
                    'device_session' => $instance,
                    'sender_number' => $remoteJid,
                    'message' => $messageContent,
                    'message_type' => $messageType,
                    'is_from_me' => $isFromMe,
                    'status' => 'pending'
                ]);

                $queueMessage->save();

                Log::info("Mensagem adicionada à fila", [
                    'message_id' => $queueMessage->id,
                    'device' => $instance
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erro ao processar evento de mensagem: " . $e->getMessage());
        }
    }
}
