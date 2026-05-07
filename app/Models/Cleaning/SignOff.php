<?php

namespace App\Models\Cleaning;

use App\Models\Store\Booking;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignOff extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'client_id',
        'signed_at',
        'photo_evidence_url',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}