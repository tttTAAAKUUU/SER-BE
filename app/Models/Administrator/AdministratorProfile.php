<?php

namespace App\Models\Administrator;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class AdministratorProfile extends Model
{
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
