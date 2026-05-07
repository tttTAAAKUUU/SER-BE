<?php

namespace App\Models\CarWash;

use App\Models\User\User;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CarWashBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_provider_profile_id',
        'car_wash_package_id',
        'car_wash_car_type_id',
        'washer_tier',
        'scheduled_at',
        'client_address',
        'total_price',
        'ser_cut',
        'washer_payout',
        'status',
        'paid_at',
        'completed_at',
        'dispute_window_closes_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'dispute_window_closes_at' => 'datetime',
        'total_price' => 'decimal:2',
        'ser_cut' => 'decimal:2',
        'washer_payout' => 'decimal:2',
    ];

    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PAID = 'paid';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class, 'service_provider_profile_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CarWashPackage::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarWashCarType::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(CarWashBookingAddon::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(CarWashDispute::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canDispute(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->dispute_window_closes_at
            && now()->lt($this->dispute_window_closes_at);
    }
}