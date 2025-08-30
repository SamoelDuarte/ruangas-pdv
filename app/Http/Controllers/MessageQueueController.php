<?php

namespace App\Http\Controllers;

use App\Models\MessageQueue;
use App\Models\Device;
use Illuminate\Http\Request;

class MessageQueueController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'device_session' => 'required|string',
            'sender_number' => 'required|string',
            'message' => 'required|string',
            'message_type' => 'required|string',
            'is_from_me' => 'required|boolean'
        ]);

        // Formata o número do remetente
        $data['sender_number'] = MessageQueue::formatNumber($data['sender_number']);

        // Cria a mensagem na fila
        $message = MessageQueue::create($data);

        return response()->json($message, 201);
    }

    public function list(Request $request)
    {
        $deviceSession = $request->get('device_session');
        
        $messages = MessageQueue::where('device_session', $deviceSession)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messages);
    }

    public function delete($id)
    {
        $message = MessageQueue::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Message deleted successfully']);
    }

    public function getMessagesForDevice($deviceSession)
    {
        $device = Device::where('session', $deviceSession)->firstOrFail();
        
        $messages = MessageQueue::where('device_session', $deviceSession)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messages);
    }
}
