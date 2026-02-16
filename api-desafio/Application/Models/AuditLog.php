<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'source',
        'before',
        'after',
        'meta',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'meta' => 'array',
    ];
}
