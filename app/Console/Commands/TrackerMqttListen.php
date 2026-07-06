<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

class TrackerMqttListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:mqtt-listen {--topic= : Topico para escutar. Exemplo: tracker/+/up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Escuta mensagens MQTT do rastreador e grava tudo em log';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $host = (string) config('mqtt.host', '127.0.0.1');
        $port = (int) config('mqtt.port', 1883);
        $username = config('mqtt.username');
        $password = config('mqtt.password');
        $clientId = (string) config('mqtt.client_id', 'ruangas-listener');
        $topic = (string) ($this->option('topic') ?: config('mqtt.topic', 'tracker/#'));
        $qos = (int) config('mqtt.qos', 1);
        $useTls = (bool) config('mqtt.tls', false);

        $this->info("Conectando no broker MQTT {$host}:{$port}...");
        $this->info("Topico: {$topic}");

        try {
            $client = new MqttClient($host, $port, $clientId);

            $settings = (new ConnectionSettings())
                ->setUsername($username)
                ->setPassword($password)
                ->setUseTls($useTls)
                ->setConnectTimeout((int) config('mqtt.connect_timeout', 10))
                ->setSocketTimeout((int) config('mqtt.socket_timeout', 5))
                ->setKeepAliveInterval((int) config('mqtt.keep_alive_interval', 60));

            $client->connect($settings, true);

            $client->subscribe($topic, function (string $topicName, string $message, bool $retained) {
                $decoded = json_decode($message, true);

                $logPayload = [
                    'topic' => $topicName,
                    'retained' => $retained,
                    'payload_raw' => $message,
                    'payload_json' => json_last_error() === JSON_ERROR_NONE ? $decoded : null,
                    'received_at' => now()->toDateTimeString(),
                ];

                Log::info('MQTT rastreador recebido', $logPayload);

                $this->line('[' . now()->format('H:i:s') . "] {$topicName} => {$message}");
            }, $qos);

            Log::info('MQTT listener iniciado', [
                'host' => $host,
                'port' => $port,
                'topic' => $topic,
                'qos' => $qos,
                'tls' => $useTls,
            ]);

            $this->info('Escuta MQTT iniciada. Pressione CTRL+C para parar.');

            $client->loop(true);
            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('Erro ao iniciar listener MQTT', [
                'erro' => $e->getMessage(),
            ]);

            $this->error('Falha no MQTT listener: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
