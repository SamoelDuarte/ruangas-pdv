<?php

namespace App\Http\Controllers;

use App\Models\Device;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
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
        $device->save();

        $qrcode = $this->getQrCode($device->session);

        return response()->json([
            'session' => $device->session,
            'id' => $device->id,
            'qrcode' => $qrcode
        ]);
    }

    public function getDevices()
    {
        Device::deleteDevicesWithNullJid(); // garanta que existe no model
        $devices = Device::orderBy('id');
        return DataTables::of($devices)->make(true);
    }

    public function updateStatus(Request $request)
    {
        $device = Device::find($request->id);

        $device->status = $request->status;
        $device->picture = $request->picture;
        $device->jid = $request->jid;
        $device->update();

        return response()->json(['status' => '1']);
    }

    public function updateName(Request $request)
    {
        $device = Device::find($request->id);
        $device->name = $request->name;
        $device->update();

        return response()->json(['status' => '1']);
    }

    public function getQrCode($session)
    {
        $url = env('APP_URL_ZAP') . '/instance/create';

        $data = [
            "instanceName" => $session,
            "qrcode"       => true,
            "integration"  => "WHATSAPP-BAILEYS"
        ];

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'apikey: ' . env('TOKEN_EVOLUTION'),
                'Content-Type: application/json'
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        $result = json_decode($response, true);

        return $result['qrcode']['base64'] ?? false;
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

        $qrCode = $this->getQrCode($device->session);

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
        $device->start_minutes = $request->start_minutes;
        $device->start_seconds = $request->start_seconds;
        $device->end_minutes = $request->end_minutes;
        $device->end_seconds = $request->end_seconds;
        $device->save();

        return response()->json(['message' => 'Dispositivo atualizado com sucesso']);
    }
}
