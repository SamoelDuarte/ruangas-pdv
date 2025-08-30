<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageQueue extends Model
{
    protected $table = 'message_queue';
    
    protected $fillable = [
        'sender_number',
        'device_session',
        'message',
        'message_type',
        'is_from_me',
        'status'
    ];

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_session', 'session');
    }
    
    // Formata o número removendo prefixos e sufixos do WhatsApp
    public static function formatNumber($number)
    {
        // Remove @s.whatsapp.net e caracteres não numéricos
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Remove prefixo 55 se existir
        if (str_starts_with($number, '55')) {
            $number = substr($number, 2);
        }
        
        return $number;
    }
}
