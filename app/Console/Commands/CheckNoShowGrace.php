<?php

namespace App\Console\Commands;

use App\Models\Store\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckNoShowGrace extends Command
{
    protected $signature = 'cleaning:check-no-show-grace';

    protected $description = 'Check for bookings where the no-show grace period has expired and trigger refund';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subMinutes(60);

        $bookings = Booking::where('no_show_grace_started_at', '<=', $cutoff)
            ->whereNotIn('status', ['completed', 'cancelled', 'no_show'])
            ->get();

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'no_show']);

            // Escrow refund stub: log for future ledger integration
            \Illuminate\Support\Facades\Log::info('Cleaning no-show: escrow refund triggered', [
                'booking_id' => $booking->id,
                'ser_commission' => $booking->ser_commission,
                'cleaner_payout' => $booking->cleaner_payout,
                'transport_deposit' => $booking->transport_deposit,
                'grace_started_at' => $booking->no_show_grace_started_at->toIso8601String(),
            ]);
        }

        $this->info("Processed {$bookings->count()} no-show bookings.");

        return Command::SUCCESS;
    }
}