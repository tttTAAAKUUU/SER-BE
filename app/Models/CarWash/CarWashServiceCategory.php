<?php

namespace App\Models\CarWash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarWashServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function packages(): HasMany
    {
        return $this->hasMany(CarWashPackage::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(CarWashAddon::class);
    }
}