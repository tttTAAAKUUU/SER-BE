<?php

namespace App\Models\Cleaning;

use App\Models\Store\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringSession extends Model
{
    protected $fillable = [
        'template_id',
        'booking_id',
        'scheduled_date',
        'status',
        'addon_config',
        'subtotal',
        'total',
        'projected_duration_minutes',
    ];

    protected $casts = [
        'addon_config' => 'array',
        'scheduled_date' => 'date',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'projected_duration_minutes' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringTemplate::class, 'template_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}