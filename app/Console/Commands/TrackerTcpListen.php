<?php

namespace App\Console\Commands;

use App\Services\Tracker\TrackerTcpCommandDispatcher;
use App\Services\Tracker\TrackerTcpMessageIngestor;
use Illuminate\Console\Command;
use Throwable;

class TrackerTcpListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:tcp-listen {--host= : Host para bind local} {--port= : Porta para bind local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Escuta conexoes TCP de rastreador e mostra payload na tela';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $host = (string) ($this->option('host') ?: config('tracker_tcp.host', '0.0.0.0'));
        $port = (int) ($this->option('port') ?: config('tracker_tcp.port', 5001));
        $timeoutSeconds = (int) config('tracker_tcp.client_timeout', 30);
        $maxBytes = (int) config('tracker_tcp.max_bytes_per_read', 4096);
        $saveMessages = (bool) config('tracker_tcp.save_messages', true);
        $ingestor = $saveMessages ? app(TrackerTcpMessageIngestor::class) : null;
        $commandDispatcher = app(TrackerTcpCommandDispatcher::class);

        $bind = "tcp://{$host}:{$port}";
        $errno = 0;
        $errstr = '';

        $server = @stream_socket_server($bind, $errno, $errstr);
        if ($server === false) {
            $this->error("Falha ao abrir listener em {$bind}: {$errstr} ({$errno})");
            return self::FAILURE;
        }

        stream_set_blocking($server, false);

        $this->info("TCP listener ativo em {$bind}");
        $this->info($saveMessages
            ? 'Modo rastreamento: salvando dados no banco e calculando permanencia por endereco.'
            : 'Modo monitoramento: sem salvar dados em banco.');
        $this->info('Pressione CTRL+C para parar.');

        $clients = [];
        $buffers = [];
        $clientImeis = [];

        try {
            while (true) {
                $read = array_merge([$server], $clients);
                $write = [];
                $except = [];

                $changed = @stream_select($read, $write, $except, 1);
                if ($changed === false) {
                    $this->warn('stream_select retornou erro, continuando...');
                    continue;
                }

                foreach ($read as $socket) {
                    if ($socket === $server) {
                        $client = @stream_socket_accept($server, 0, $peer);
                        if ($client !== false) {
                            stream_set_blocking($client, false);
                            stream_set_timeout($client, $timeoutSeconds);
                            $id = (int) $client;
                            $clients[$id] = $client;
                            $buffers[$id] = '';
                            $this->line('[' . now()->format('H:i:s') . "] CONECTOU: {$peer}");
                        }
                        continue;
                    }

                    $id = (int) $socket;
                    $peer = 'desconhecido';
                    @stream_socket_get_name($socket, true);
                    $peerName = @stream_socket_get_name($socket, true);
                    if (is_string($peerName) && $peerName !== '') {
                        $peer = $peerName;
                    }

                    $data = @fread($socket, $maxBytes);

                    if ($data === '' || $data === false) {
                        if (feof($socket)) {
                            fclose($socket);
                            unset($clients[$id]);
                            unset($buffers[$id]);
                            unset($clientImeis[$id]);
                            $this->line('[' . now()->format('H:i:s') . "] DESCONECTOU: {$peer}");
                        }
                        continue;
                    }

                    $buffers[$id] = ($buffers[$id] ?? '') . $data;

                    while (($end = strpos($buffers[$id], '$')) !== false) {
                        $frame = substr($buffers[$id], 0, $end + 1);
                        $buffers[$id] = substr($buffers[$id], $end + 1);

                        $clean = trim(preg_replace('/[^\P{C}\n\r\t]/u', '.', $frame));
                        if ($clean === '') {
                            continue;
                        }

                        $hex = strtoupper(bin2hex($frame));
                        $hexPreview = strlen($hex) > 200 ? substr($hex, 0, 200) . '...' : $hex;

                        $this->line('[' . now()->format('H:i:s') . "] {$peer} => {$clean}");
                        $this->line('HEX: ' . $hexPreview);

                        $imei = $this->extractImeiFromFrame($clean);
                        if ($imei !== null) {
                            $clientImeis[$id] = $imei;
                        }

                        if ($ingestor !== null) {
                            try {
                                $ingestor->ingest($clean, $peer);
                            } catch (Throwable $exception) {
                                $this->warn('Falha ao salvar frame: ' . $exception->getMessage());
                            }
                        }
                    }
                }

                $connectionsByImei = [];
                foreach ($clientImeis as $clientId => $imei) {
                    if (isset($clients[$clientId])) {
                        $connectionsByImei[$imei] = $clients[$clientId];
                    }
                }

                $commandDispatcher->dispatchPendingForConnections($connectionsByImei);
            }
        } catch (Throwable $e) {
            $this->error('Erro no listener TCP: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            foreach ($clients as $client) {
                @fclose($client);
            }
            @fclose($server);
        }

        return self::SUCCESS;
    }

    private function extractImeiFromFrame(string $frame): ?string
    {
        $trimmed = trim($frame);
        if ($trimmed === '') {
            return null;
        }

        if (str_ends_with($trimmed, '$')) {
            $trimmed = substr($trimmed, 0, -1);
        }

        $parts = explode(',', $trimmed);
        if (count($parts) < 3) {
            return null;
        }

        $imei = preg_replace('/\D+/', '', (string) ($parts[2] ?? ''));

        return $imei !== '' ? $imei : null;
    }
}
