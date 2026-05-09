<?php

namespace App\Models\Store;

use App\Models\Business\Store\Employee;
use App\Models\Business\Store\StoreService;
use App\Models\Business\Store\StoreServiceAddon;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\Store\BookingFactory> */
    use HasFactory;

    public const STATUSES = [
        'pending_payment', 'paid_escrow', 'in_transit', 'arrived',
        'in_progress', 'sign_off_pending', 'completed', 'cancelled', 'no_show',
    ];

    public const VALID_TRANSITIONS = [
        'pending_payment' => ['paid_escrow', 'cancelled'],
        'paid_escrow'     => ['in_transit', 'cancelled'],
        'in_transit'      => ['arrived', 'cancelled'],
        'arrived'         => ['in_progress', 'cancelled'],
        'in_progress'    => ['sign_off_pending', 'cancelled'],
        'sign_off_pending'=> ['completed'],
        'completed'       => [],
        'cancelled'       => [],
        'no_show'         => [],
    ];

    protected $fillable = [
        'user_id',
        'store_service_id',
        'employee_id',
        'service_id',
        'time_category',
        'time',
        'service_location',
        'package_type',
        'room_tier',
        'bathroom_count',
        'scheduling_mode',
        'recurring_days',
        'start_date',
        'projected_duration_minutes',
        'break_minutes',
        'distance_km',
        'transport_deposit',
        'subtotal',
        'total',
        'ser_commission',
        'cleaner_payout',
        'status',
        'started_at',
        'sign_off_at',
        'no_show_grace_started_at',
        'liability_waiver_applied',
    ];

    protected $casts = [
        'recurring_days' => 'array',
        'bathroom_count' => 'integer',
        'projected_duration_minutes' => 'integer',
        'break_minutes' => 'integer',
        'distance_km' => 'decimal:2',
        'transport_deposit' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'ser_commission' => 'decimal:2',
        'cleaner_payout' => 'decimal:2',
        'sign_off_at' => 'datetime',
        'no_show_grace_started_at' => 'datetime',
        'started_at' => 'datetime',
        'liability_waiver_applied' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storeService(): BelongsTo
    {
        return $this->belongsTo(StoreService::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    public function upgradeRequests(): HasMany
    {
        return $this->hasMany(\App\Models\Cleaning\UpgradeRequest::class);
    }

    public function signOffs(): HasMany
    {
        return $this->hasMany(\App\Models\Cleaning\SignOff::class);
    }

    public function getProviderIdAttribute($value)
    {
        return $value ?? $this->employee_id;
    }

    public function canTransitionTo(string $status): bool
    {
        $allowed = self::VALID_TRANSITIONS[$this->status] ?? [];
        return in_array($status, $allowed);
    }

    public function markInTransit(): void
    {
        if (!$this->canTransitionTo('in_transit')) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status} to in_transit.");
        }
        $this->update(['status' => 'in_transit']);
    }

    public function markArrived(): void
    {
        if (!$this->canTransitionTo('arrived')) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status} to arrived.");
        }
        $this->update(['status' => 'arrived']);
    }

    public function markStarted(): void
    {
        if (!$this->canTransitionTo('in_progress')) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status} to in_progress.");
        }
        $this->update(['status' => 'in_progress', 'started_at' => now()]);
    }

    public function markSignOffPending(): void
    {
        if (!$this->canTransitionTo('sign_off_pending')) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status} to sign_off_pending.");
        }
        $this->update(['status' => 'sign_off_pending']);
    }

    public function isPast(): bool
    {
        return Carbon::parse($this->time)->isPast();
    }
}
