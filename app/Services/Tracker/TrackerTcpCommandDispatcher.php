<?php

namespace App\Services\Tracker;

use App\Models\TrackerCommand;

class TrackerTcpCommandDispatcher
{
    public function dispatchPendingForConnections(array $connectionsByImei): void
    {
        if ($connectionsByImei === []) {
            return;
        }

        $commands = TrackerCommand::query()
            ->where('status', 'pending')
            ->whereIn('imei', array_keys($connectionsByImei))
            ->orderBy('id')
            ->get();

        foreach ($commands as $command) {
            $socket = $connectionsByImei[$command->imei] ?? null;
            if (!is_resource($socket)) {
                continue;
            }

            $payload = $command->command_payload;
            $bytes = @fwrite($socket, $payload);

            if ($bytes === false || $bytes < strlen($payload)) {
                $command->update([
                    'status' => 'failed',
                    'error_message' => 'Falha ao escrever o comando no socket ativo.',
                    'completed_at' => now(),
                ]);
                continue;
            }

            $command->update([
                'status' => 'sent',
                'sent_at' => now(),
                'completed_at' => now(),
            ]);
        }
    }
}
