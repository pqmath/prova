<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EventInbox extends Model
{
    use HasUuids;

    protected $table = 'event_inboxes';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'idempotency_key',
        'source',
        'type',
        'payload',
        'status',
        'processed_at',
        'error',
        'publish_attempts',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'publish_attempts' => 'integer',
    ];
}
