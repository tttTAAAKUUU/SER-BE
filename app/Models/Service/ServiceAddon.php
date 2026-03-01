<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceAddon extends Model
{
    /** @use HasFactory<\Database\Factories\ServicesFactory> */
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'description',
        'price',
        'duration_minutes'
    ];

    public function service(): BelongsTo {

        return $this->belongsTo(Service::class);
    }
}
