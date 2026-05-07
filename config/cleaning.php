<?php

return [
    // Rate per hour for add-on pricing formula: ((duration_minutes / 60) * addon_rate) * count
    'addon_rate' => env('CLEANING_ADDON_RATE', 33.27),

    // Default transport rate per km
    'transport_rate' => env('CLEANING_TRANSPORT_RATE', 4.80),

    // Hard cap on session duration per provider (minutes)
    'max_session_minutes' => env('CLEANING_MAX_SESSION_MINUTES', 480),

    // No-show grace period in minutes
    'no_show_grace_minutes' => env('CLEANING_NO_SHOW_GRACE_MINUTES', 60),

    // Revenue split — cleaner percentage (85%)
    'cleaner_split' => env('CLEANING_CLEANER_SPLIT', 0.85),

    // Revenue split — SER percentage (15%)
    'ser_split' => env('CLEANING_SER_SPLIT', 0.15),

    // Equipment disclaimer shown at checkout
    'equipment_disclaimer' => 'Please note: Clients must provide all necessary detergents, cleaning equipment, and supplies. The service provider will arrive with only professional tools.',
];