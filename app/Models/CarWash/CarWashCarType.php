<?php

namespace App\Models\CarWash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarWashCarType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'effort_level'];

    public function packagePrices(): HasMany
    {
        return $this->hasMany(CarWashPackagePrice::class);
    }

    public function addonPrices(): HasMany
    {
        return $this->hasMany(CarWashAddonPrice::class);
    }
}