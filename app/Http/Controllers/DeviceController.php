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
        // Apaga os dispositivos antigos
        Device::whereNull('status')->delete();

        // Cria o dispositivo com apenas a session
        $device = new Device();
        $device->session = Utils::createCode();
        $device->save();

        return view('sistema.dispositivo.create', compact('device'));
    }

    public function store(Request $request)
    {
        // Apaga os dispositivos antigos
        Device::whereNull('status')->delete();
        $request->validate([
            'nome' => 'required|string|max:255'
        ]);

        // Cria nova sessão
        $device = new Device();
        $device->session = Utils::createCode();
        $device->name = $request->nome;
        $device->save();

        // Gera o QR Code da sessão
        $qrcode = $this->getQrCode($device->session);

        return response()->json([
            'session' => $device->session,
            'id' => $device->id,
            'qrcode' => $qrcode
        ]);
    }



    public function getDevices()
    {
        Device::deleteDevicesWithNullJid();
        $devices = Device::orderBy('id');
        return DataTables::of($devices)->make(true);
    }

    public function updateStatus(Request $request)
    {
        $device = Device::where('id', $request->id)->first();



        $device->status = $request->status;
        $device->picture = $request->picture;
        $device->jid = $request->jid;
        $device->update();

        echo json_encode(array('status' => '1'));
    }

    public function updateName(Request $request)
    {
        $device = Device::where('id', $request->id)->first();
        $device->name = $request->name;
        $device->update();
        echo json_encode(array('status' => '1'));
    }

    function getQrCode($session)
    {
        // URL da requisição
        $url = env('APP_URL_ZAP') . '/instance/create';

        // Dados da requisição
        $data = [
            "instanceName" => $session,
            "qrcode"       => true,
            "integration"  => "WHATSAPP-BAILEYS"
        ];

        // Configuração da requisição
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

        // Inicializar a sessão curl
        $ch = curl_init();

        // Configurar as opções do curl
        curl_setopt_array($ch, $options);

        // Executar a requisição e obter a resposta
        $response = curl_exec($ch);

        // Verificar se ocorreu algum erro
        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }

        // Fechar a sessão curl
        curl_close($ch);

        // Tratar a resposta (decodificar JSON)
        $result = json_decode($response, true);

        // Verificar se veio o QR Code
        if (isset($result['qrcode']['base64'])) {
            return $result['qrcode']['base64'];
        }

        return false;
    }


    public function gerarQr(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:devices,id',
            'nome' => 'required|string|max:255',
        ]);

        $device = Device::find($request->id);
        $device->name = $request->nome;
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
            $response = $client->request('GET',  env('APP_URL_ZAP')."/instance/connectionState/{$request->sessionId}", [
                'headers' => [
                    'apikey' => env('TOKEN_EVOLUTION') // Substitua pela sua chave real
                ]
            ]);

            $body = json_decode($response->getBody(), true);

            // Retorna como JSON
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

        $device->delete();


        return back()->with('success', 'Deletado Com Sucesso.');
    }
}
