<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Occurrence extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'external_id',
        'type',
        'status',
        'description',
        'reported_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
    ];
}
