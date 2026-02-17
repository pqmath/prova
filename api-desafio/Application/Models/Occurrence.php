<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Occurrence extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
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

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }
}
