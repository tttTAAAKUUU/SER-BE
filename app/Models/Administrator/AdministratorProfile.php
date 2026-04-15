<?php

namespace App\Models\Administrator;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdministratorProfile extends Model
{
    /** @use HasFactory<\Database\Factories\Administrator\AdministratorProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'profile_image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
