<?php

namespace App\Http\Controllers;

use App\Models\Device;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DeviceController extends Controller
{
    public function dash()
    {
        return view('dashboard.index');
    }

    public function index()
    {
        return view('sistema.dispositivo.index');
    }

    public function create()
    {
        Device::whereNull('status')->delete();

        $device = new Device();
        $device->session = Utils::createCode();
        $device->save();

        return view('sistema.dispositivo.create', compact('device'));
    }

    public function store(Request $request)
    {
        Device::whereNull('status')->delete();

        $request->validate([
            'nome' => 'required|string|max:255',
            'data_ultima_recarga' => 'required|date',
            'start_minutes' => 'required|integer|min:0',
            'start_seconds' => 'required|integer|min:0|max:59',
            'end_minutes' => 'required|integer|min:0',
            'end_seconds' => 'required|integer|min:0|max:59'
        ]);

        // Validação do intervalo de tempo
        $startTotal = ($request->start_minutes * 60) + $request->start_seconds;
        $endTotal = ($request->end_minutes * 60) + $request->end_seconds;

        if ($startTotal >= $endTotal) {
            return response()->json([
                'errors' => [
                    'time_interval' => ['O intervalo final deve ser maior que o inicial']
                ]
            ], 422);
        }

        $device = new Device();
        $device->session = Utils::createCode();
        $device->name = $request->nome;
        $device->start_minutes = $request->start_minutes;
        $device->start_seconds = $request->start_seconds;
        $device->end_minutes = $request->end_minutes;
        $device->end_seconds = $request->end_seconds;
        $device->data_ultima_recarga = $request->data_ultima_recarga;
        $device->save();

        $qrcode = $this->getQrCode($device->session, $device->name);
        
        // Configurar webhook para o novo dispositivo
        $this->configurarWebhookDevice($device->session);
        $this->configurarChatwootDevice($device);

        return response()->json([
            'session' => $device->session,
            'id' => $device->id,
            'qrcode' => $qrcode
        ]);
    }

    public function getDevices()
    {
        // Comentando temporariamente para debug - você pode descomentar depois
        // Device::deleteDevicesWithNullJid(); 
        
        $devices = Device::orderBy('id');
        return DataTables::of($devices)
            ->addColumn('actions', function($device) {
                return '
                    <div class="btn-group">
                        <a href="#" class="btn btn-sm btn-info edit" onclick="editDevice(' . $device->id . ')">
                            <i class="fas fa-edit"></i>
                        </a> 
                        <a href="#" class="btn btn-sm btn-danger delete" data-toggle="modal" data-target="#modalDelete" onclick="configModalDelete(' . $device->id . ')">
                            <i class="far fa-trash-alt"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function updateStatus(Request $request)
    {
        $device = Device::find($request->id);
        $previousStatus = $device?->status;

        $device->status = $request->status;
        $device->picture = $request->picture;
        $device->jid = $request->jid;
        $device->update();

        $isNowConnected = strtolower((string) $request->status) === 'open';
        $wasConnected = strtolower((string) $previousStatus) === 'open';

        if ($isNowConnected && !$wasConnected && !empty($device->session)) {
            $this->configurarChatwootDevice($device);
        }

        return response()->json(['status' => '1']);
    }

    public function updateName(Request $request)
    {
        $device = Device::find($request->id);
        $device->name = $request->name;
        $device->update();

        return response()->json(['status' => '1']);
    }

    public function getQrCode($session, $deviceName = null)
    {
        $url = env('APP_URL_ZAP') . '/instance/create';

        $data = [
            "instanceName" => $session,
            "qrcode"       => true,
            "integration"  => "WHATSAPP-BAILEYS"
        ];

        $chatwootAccountId = env('CHATWOOT_ACCOUNT_ID');
        $chatwootToken = env('CHATWOOT_TOKEN');
        $chatwootUrl = env('CHATWOOT_URL');

        // Integra Chatwoot já na criação da instância conforme a documentação da Evolution.
        if (!empty($chatwootAccountId) && !empty($chatwootToken) && !empty($chatwootUrl)) {
            $data['chatwootAccountId'] = (string) $chatwootAccountId;
            $data['chatwootToken'] = $chatwootToken;
            $data['chatwootUrl'] = rtrim($chatwootUrl, '/');
            $data['chatwootSignMsg'] = true;
            $data['chatwootReopenConversation'] = true;
            $data['chatwootConversationPending'] = false;
            $data['chatwootImportContacts'] = true;
            $data['chatwootNameInbox'] = !empty($deviceName) ? $deviceName : 'evolution';
            $data['chatwootMergeBrazilContacts'] = true;
            $data['chatwootImportMessages'] = true;
            $data['chatwootDaysLimitImportMessages'] = (int) env('CHATWOOT_DAYS_LIMIT_IMPORT_MESSAGES', 3);
            $data['chatwootOrganization'] = env('CHATWOOT_ORGANIZATION', 'BOT');
            $data['chatwootLogo'] = env('CHATWOOT_LOGO_URL', '');
        }

        $requestTimeout = (int) env('EVOLUTION_REQUEST_TIMEOUT', 30);
        $connectTimeout = (int) env('EVOLUTION_CONNECT_TIMEOUT', 10);
        $maxQrRetries = (int) env('EVOLUTION_QRCODE_RETRIES', 8);
        $retryDelayMs = (int) env('EVOLUTION_QRCODE_RETRY_DELAY_MS', 1500);

        try {
            $client = new Client([
                'timeout' => $requestTimeout,
                'connect_timeout' => $connectTimeout,
            ]);

            $response = $client->request('POST', $url, [
                'headers' => [
                    'apikey' => env('TOKEN_EVOLUTION'),
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            $result = json_decode((string) $response->getBody(), true);
            $qrCode = $this->extractQrCodeFromResponse($result);

            if (!$qrCode) {
                for ($attempt = 1; $attempt <= $maxQrRetries; $attempt++) {
                    if ($retryDelayMs > 0) {
                        usleep($retryDelayMs * 1000);
                    }

                    $qrCode = $this->fetchQrCodeFromInstance($session, $client);

                    if ($qrCode) {
                        break;
                    }
                }
            }

            if (!$qrCode) {
                Log::warning("QR Code não retornado pela Evolution para a sessão {$session} após {$maxQrRetries} tentativas");
            }

            return $qrCode ?: false;
        } catch (\Exception $e) {
            Log::error("Erro ao criar/buscar QR Code da sessão {$session}: " . $e->getMessage());
            return false;
        }
    }

    private function fetchQrCodeFromInstance($session, Client $client)
    {
        $baseUrl = rtrim((string) env('APP_URL_ZAP'), '/');

        $attempts = [
            ['GET', "/instance/connect/{$session}"],
            ['POST', "/instance/connect/{$session}"],
            ['GET', "/instance/qrCode/{$session}"],
            ['GET', "/instance/connectionState/{$session}"],
        ];

        foreach ($attempts as [$method, $path]) {
            try {
                $response = $client->request($method, $baseUrl . $path, [
                    'headers' => [
                        'apikey' => env('TOKEN_EVOLUTION'),
                        'Content-Type' => 'application/json',
                    ],
                ]);

                $result = json_decode((string) $response->getBody(), true);
                $qrCode = $this->extractQrCodeFromResponse($result);

                if ($qrCode) {
                    return $qrCode;
                }
            } catch (\Exception $e) {
                // Tenta o próximo endpoint sem interromper o fluxo.
            }
        }

        return false;
    }

    private function extractQrCodeFromResponse($result)
    {
        if (!is_array($result)) {
            return false;
        }

        $paths = [
            ['qrcode', 'base64'],
            ['qrcode', 'code'],
            ['qrcode'],
            ['qrCode', 'base64'],
            ['qrCode'],
            ['base64'],
            ['qr'],
            ['data', 'qrcode', 'base64'],
            ['data', 'qrcode'],
            ['response', 'qrcode', 'base64'],
            ['response', 'qrcode'],
        ];

        foreach ($paths as $path) {
            $value = $result;

            foreach ($path as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    $value = null;
                    break;
                }

                $value = $value[$segment];
            }

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return false;
    }

    public function gerarQr(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:devices,id',
            'nome' => 'required|string|max:255',
            'start_minutes' => 'required|integer|min:0',
            'start_seconds' => 'required|integer|min:0|max:59',
            'end_minutes' => 'required|integer|min:0',
            'end_seconds' => 'required|integer|min:0|max:59'
        ]);

        $device = Device::find($request->id);
        $device->name = $request->nome;
        $device->start_minutes = $request->start_minutes;
        $device->start_seconds = $request->start_seconds;
        $device->end_minutes = $request->end_minutes;
        $device->end_seconds = $request->end_seconds;
        $device->save();

        $qrCode = $this->getQrCode($device->session, $device->name);

        if ($qrCode) {
            return response()->json(['qr_code' => $qrCode]);
        } else {
            return response()->json(['error' => 'Erro ao gerar QR Code.'], 500);
        }
    }

    public function getStatus(Request $request)
    {
        $client = new Client();

        try {
            $response = $client->request('GET', env('APP_URL_ZAP') . "/instance/connectionState/{$request->sessionId}", [
                'headers' => [
                    'apikey' => env('TOKEN_EVOLUTION')
                ]
            ]);

            $body = json_decode($response->getBody(), true);

            return response()->json([
                'status' => true,
                'data' => $body
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkEvolutionStatus(Request $request)
    {
        $request->validate([
            'session' => 'required|string'
        ]);

        $client = new Client();

        try {
            $response = $client->request('GET', env('APP_URL_ZAP') . "/instance/connectionState/{$request->session}", [
                'headers' => [
                    'apikey' => env('TOKEN_EVOLUTION')
                ]
            ]);

            $body = json_decode($response->getBody(), true);
            
            // Verificar se está conectado (open)
            $isConnected = isset($body['instance']['state']) && $body['instance']['state'] === 'open';
            
            return response()->json([
                'success' => true,
                'connected' => $isConnected,
                'status' => $body['instance']['state'] ?? 'unknown',
                'data' => $body
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'connected' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $device = Device::where('id', $request->id_device)->first();

        if ($device) {
            $device->delete();
            return back()->with('success', 'Deletado Com Sucesso.');
        }

        return back()->with('error', 'Dispositivo não encontrado.');
    }

    public function getDevice($id)
    {
        $device = Device::find($id);
        if (!$device) {
            return response()->json(['message' => 'Dispositivo não encontrado'], 404);
        }
        return response()->json($device);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:devices,id',
            'name' => 'required|string|max:255',
            'data_ultima_recarga' => 'required|date',
            'start_minutes' => 'required|integer|min:0',
            'start_seconds' => 'required|integer|min:0|max:59',
            'end_minutes' => 'required|integer|min:0',
            'end_seconds' => 'required|integer|min:0|max:59'
        ]);

        // Validação do intervalo de tempo
        $startTotal = ($request->start_minutes * 60) + $request->start_seconds;
        $endTotal = ($request->end_minutes * 60) + $request->end_seconds;

        if ($startTotal >= $endTotal) {
            return response()->json([
                'message' => 'O intervalo final deve ser maior que o inicial'
            ], 422);
        }

        $device = Device::find($request->id);
        $device->name = $request->name;
        $device->data_ultima_recarga = $request->data_ultima_recarga;
        $device->start_minutes = $request->start_minutes;
        $device->start_seconds = $request->start_seconds;
        $device->end_minutes = $request->end_minutes;
        $device->end_seconds = $request->end_seconds;
        $device->save();

        return response()->json(['message' => 'Dispositivo atualizado com sucesso']);
    }

    public function monitorStatus()
    {
        return view('sistema.dispositivo.monitor');
    }

    public function getStatusAll()
    {
        $devices = Device::whereNotNull('session')
            ->select('id', 'name', 'session', 'status', 'jid', 'updated_at', 'data_ultima_recarga')
            ->get()
            ->map(function ($device) {
                $statusColor = 'danger'; // default para offline
                if ($device->status === 'open') {
                    $statusColor = 'success';
                } elseif ($device->status === 'connecting') {
                    $statusColor = 'warning';
                } elseif ($device->status === 'DISCONNECTED' || $device->status === 'disconnected') {
                    $statusColor = 'danger';
                }
                
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'session' => $device->session,
                    'status' => $device->status,
                    'display_status' => $device->display_status,
                    'jid' => $device->jid,
                    'updated_at' => $device->updated_at?->format('d/m/Y H:i:s'),
                    'data_ultima_recarga' => $device->data_ultima_recarga_formatada,
                    'status_color' => $statusColor,
                    'last_check' => $device->updated_at?->diffForHumans()
                ];
            });

        return response()->json($devices);
    }

    public function forceStatusCheck()
    {
        try {
            Artisan::call('device:check-status', ['--force' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Verificação de status iniciada com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar verificação: ' . $e->getMessage()
            ], 500);
        }
    }

    public function atualizarUltimaRecarga(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:devices,id'
        ]);

        $device = Device::find($request->id);
        $device->data_ultima_recarga = now();
        $device->save();

        return response()->json([
            'message' => 'Data da última recarga atualizada com sucesso',
            'data_ultima_recarga' => $device->data_ultima_recarga_formatada
        ]);
    }

    public function reconectar(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:devices,id'
        ]);

        $device = Device::find($request->id);
        
        try {
            // Primeiro, desconectar a sessão atual se existir
            $this->desconectarSessao($device->session);
            
            // Gerar nova sessão
            $device->session = Utils::createCode();
            $device->status = null;
            $device->jid = null;
            $device->picture = null;
            $device->data_ultima_recarga = now();
            $device->save();

            // Gerar novo QR code
            $qrcode = $this->getQrCode($device->session, $device->name);
            
            // Configurar webhook para o dispositivo reconectado
            $this->configurarWebhookDevice($device->session);
            $this->configurarChatwootDevice($device);

            if ($qrcode) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reconexão iniciada com sucesso',
                    'qrcode' => $qrcode,
                    'session' => $device->session
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar QR Code para reconexão'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro durante reconexão: ' . $e->getMessage()
            ], 500);
        }
    }

    private function desconectarSessao($session)
    {
        try {
            $client = new Client();
            $response = $client->request('DELETE', env('APP_URL_ZAP') . "/instance/logout/{$session}", [
                'headers' => [
                    'apikey' => env('TOKEN_EVOLUTION')
                ]
            ]);
            return true;
        } catch (\Exception $e) {
            // Log do erro se necessário, mas não interrompe o processo
            return false;
        }
    }

    public function updateRecarga(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:devices,id'
        ]);

        try {
            $device = Device::find($request->id);
            $device->data_ultima_recarga = now();
            $device->save();

            return response()->json([
                'success' => true,
                'message' => 'Última recarga atualizada com sucesso',
                'data_ultima_recarga' => $device->data_ultima_recarga_formatada
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar última recarga: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configura o webhook para um dispositivo específico
     */
    private function configurarWebhookDevice($session)
    {
        try {
            $client = new Client();
            
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

            $url = env('APP_URL_ZAP') . "/webhook/set/{$session}";
            $response = $client->request('POST', $url, [
                'headers' => $headers,
                'body' => $body
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Log::info("Webhook configurado com sucesso para sessão: {$session}");
                return true;
            } else {
                Log::warning("Resposta inesperada ao configurar webhook da sessão {$session}: " . $response->getStatusCode());
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Erro ao configurar webhook da sessão {$session}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configura a integração do Chatwoot para um dispositivo conectado
     */
    private function configurarChatwootDevice(Device $device)
    {
        $chatwootAccountId = env('CHATWOOT_ACCOUNT_ID');
        $chatwootToken = env('CHATWOOT_TOKEN');
        $chatwootUrl = env('CHATWOOT_URL');

        if (empty($chatwootAccountId) || empty($chatwootToken) || empty($chatwootUrl)) {
            Log::warning("Chatwoot não configurado no .env para a sessão {$device->session}");
            return false;
        }

        try {
            $client = new Client();

            $payload = [
                'enabled' => true,
                'accountId' => (string) $chatwootAccountId,
                'token' => $chatwootToken,
                'url' => rtrim($chatwootUrl, '/'),
                'signMsg' => true,
                'reopenConversation' => true,
                'conversationPending' => false,
                'nameInbox' => $device->name ?: 'evolution',
                'mergeBrazilContacts' => true,
                'importContacts' => true,
                'importMessages' => true,
                'daysLimitImportMessages' => 2,
                'signDelimiter' => "\n",
                'autoCreate' => true,
                'auto_create' => true,
                'organization' => env('CHATWOOT_ORGANIZATION', 'BOT'),
                'logo' => env('CHATWOOT_LOGO_URL', ''),
            ];

            $url = env('APP_URL_ZAP') . "/chatwoot/set/{$device->session}";
            $response = $client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'apikey' => env('TOKEN_EVOLUTION'),
                ],
                'json' => $payload,
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Log::info("Chatwoot configurado com sucesso para sessão: {$device->session}");
                return true;
            }

            Log::warning("Resposta inesperada ao configurar Chatwoot da sessão {$device->session}: " . $response->getStatusCode() . ' | body: ' . (string) $response->getBody());
            return false;
        } catch (\Exception $e) {
            Log::error("Erro ao configurar Chatwoot da sessão {$device->session}: " . $e->getMessage());
            return false;
        }
    }
}
