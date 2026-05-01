<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    /** @use HasFactory<\Database\Factories\Service\ServiceCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'service_category_id', 'id');
    }

    public function hasServices(): bool
    {
        return $this->services()->exists();
    }
}
