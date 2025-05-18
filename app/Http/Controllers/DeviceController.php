<?php

namespace App\Http\Controllers;

use App\Models\Device;
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
        $url = env('APP_URL_ZAP') . '/sessions/add';

        // Dados da requisição
        $data = array(
            'sessionId' => $session // Substitua $session pela sua variável contendo os dados
        );

        // Configuração da requisição
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'secret: $2a$12$VruN7Mf0FsXW2mR8WV0gTO134CQ54AmeCR.ml3wgc9guPSyKtHMgC',
                'Content-Type: application/json'
            )
        );

        // Inicializar a sessão curl
        $ch = curl_init();

        // Configurar as opções do curl
        curl_setopt_array($ch, $options);

        // Executar a requisição e obter a resposta
        $response = curl_exec($ch);

        // Verificar se ocorreu algum erro
        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        }

        // Fechar a sessão curl
        curl_close($ch);

        // Tratar a resposta (no caso de JSON, decodificar o JSON)
        $result = json_decode($response, true);

        // Exemplo de utilização dos dados da resposta
        if (isset($result['qr'])) {
            return   $result['qr'];
            // Faça o que for necessário com a imagem do QR code
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


        $url = env('APP_URL_ZAP') . "/sessions/" . $request->sessionId . "/status";

        $headers = array(
            'secret: $2a$12$VruN7Mf0FsXW2mR8WV0gTO134CQ54AmeCR.ml3wgc9guPSyKtHMgC'
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição cURL: ' . curl_error($ch);
        }

        curl_close($ch);

        // A variável $response contém a resposta da requisição
        // Você pode processar os dados recebidos conforme necessário
        echo $response;
    }



    public function delete(Request $request)
    {


        $device = Device::where('id', $request->id_device)->first();

        $device->delete();


        return back()->with('success', 'Deletado Com Sucesso.');
    }
}
