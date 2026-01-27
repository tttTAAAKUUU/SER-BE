<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServicesFactory> */
    use HasFactory;

    protected $fillable = [
        'service_category_id',
        'name',
        'description',
        'price',
    ];

    public function serviceCategory(): BelongsTo {

        return $this->belongsTo(ServiceCategory::class);
    }
}
