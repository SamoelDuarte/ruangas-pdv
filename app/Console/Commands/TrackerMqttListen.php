<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    protected $description = 'Escuta mensagens MQTT do rastreador e mostra na tela';

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
                $retainedTag = $retained ? ' [retained]' : '';
                $this->line('[' . now()->format('H:i:s') . "] {$topicName}{$retainedTag} => {$message}");
            }, $qos);

            $this->info('Escuta MQTT iniciada. Pressione CTRL+C para parar.');

            $client->loop(true);
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Falha no MQTT listener: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
