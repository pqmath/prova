<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;
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
