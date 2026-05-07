<?php

namespace App\Console\Commands\CarWash;

use App\Models\CarWash\WasherEquipmentChecklist;
use App\Models\CarWash\WasherTierApplication;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Console\Command;

class FlagOverdueReverification extends Command
{
    protected $signature = 'car-wash:flag-overdue-reverification';

    protected $description = 'Flag washers whose annual equipment re-certification is due';

    public function handle(): int
    {
        $dueProfiles = ServiceProviderProfile::where('washer_next_verification_at', '<=', now())
            ->whereNotNull('washer_next_verification_at')
            ->where('washer_tier', '!=', null)
            ->get();

        foreach ($dueProfiles as $profile) {
            // Flag as unverified pending review
            $profile->update(['washer_equipment_verified' => false]);

            // Create pending application for admin review
            $checklist = $profile->equipmentChecklist;
            if ($checklist) {
                WasherTierApplication::create([
                    'service_provider_profile_id' => $profile->id,
                    'washer_equipment_checklist_id' => $checklist->id,
                    'requested_tier' => $profile->washer_tier,
                    'status' => WasherTierApplication::STATUS_PENDING,
                ]);
            }

            $this->info("Flagged washer {$profile->id} ({$profile->first_name} {$profile->last_name}) for re-verification");
        }

        $this->info("Done. Flagged {$dueProfiles->count()} washers for re-verification.");

        return 0;
    }
}