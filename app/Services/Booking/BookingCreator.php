<?php

namespace App\Services\Booking;

use App\Models\Business\Store;
use App\Models\Business\Store\StoreService;
use App\Models\Business\Store\StoreServiceAddon;
use App\Models\Store\Booking;
use App\Models\User\User;
use App\Services\Booking\Exceptions\AddonNotBelongsToServiceException;
use App\Services\Booking\Exceptions\EmployeeDoubleBookingException;
use App\Services\Booking\Exceptions\UserNotCustomerOfStoreException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingCreator
{
    public function __construct(
        private User $customer,
    ) {}

    private function customerHasPriorBooking(Store $store): bool
    {
        return Booking::where('user_id', $this->customer->id)
            ->whereHas('storeService', fn($q) => $q->where('store_id', $store->id))
            ->exists();
    }

    private function addonsBelongToService(Collection $addonIds, StoreService $storeService): bool
    {
        $validAddonIds = StoreServiceAddon::where('store_service_id', $storeService->id)
            ->pluck('id');

        return $addonIds->every(fn($id) => $validAddonIds->contains($id));
    }

    private function hasTimeConflict(int $employeeId, Carbon $start, int $durationMinutes): bool
    {
        $existingBookings = Booking::where('employee_id', $employeeId)->get();

        foreach ($existingBookings as $existing) {
            $existingStart = Carbon::parse($existing->time);
            $existingEnd = $existingStart->copy()->addMinutes(
                $existing->storeService->service->duration_minutes
            );

            $newEnd = $start->copy()->addMinutes($durationMinutes);

            if ($start->lt($existingEnd) && $newEnd->gt($existingStart)) {
                return true;
            }
        }

        return false;
    }

    public function create(array $data, bool $customerInitiated = true): Booking
    {
        $storeService = StoreService::with('service')->find($data['store_service_id']);

        if (!$storeService) {
            throw new \InvalidArgumentException("Store service {$data['store_service_id']} not found");
        }

        $store = $storeService->store;

        if ($customerInitiated && !$this->customerHasPriorBooking($store)) {
            throw new UserNotCustomerOfStoreException($this->customer->id, $store->id);
        }

        if ($storeService->store_id !== $store->id) {
            throw new \InvalidArgumentException(
                "Store service {$data['store_service_id']} does not belong to store {$store->id}"
            );
        }

        $addonIds = collect($data['addons'] ?? [])->pluck('store_service_addon_id');

        if ($addonIds->isNotEmpty() && !$this->addonsBelongToService($addonIds, $storeService)) {
            throw new AddonNotBelongsToServiceException($addonIds->first(), $storeService->id);
        }

        $requestedTime = Carbon::parse($data['time']);
        $durationMinutes = $storeService->service->duration_minutes;

        if ($this->hasTimeConflict($data['employee_id'], $requestedTime, $durationMinutes)) {
            throw new EmployeeDoubleBookingException($data['employee_id'], $data['time']);
        }

        return DB::transaction(function () use ($data, $addonIds) {
            $booking = Booking::create([
                'user_id' => $this->customer->id,
                'store_service_id' => $data['store_service_id'],
                'employee_id' => $data['employee_id'],
                'time_category' => $data['time_category'],
                'time' => $data['time'],
                'service_location' => $data['service_location'],
            ]);

            foreach ($addonIds as $addonId) {
                $booking->addons()->create(['store_service_addon_id' => $addonId]);
            }

            return $booking;
        });
    }
}