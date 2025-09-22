<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'message_id', 'lead_id', 'from', 'to', 'message', 
        'direction', 'status', 'timestamp', 'raw_payload'
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'timestamp' => 'datetime',
    ];
}

