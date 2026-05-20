# Domestic Cleaning Marketplace — Product Requirements Document

## Problem Statement

SER currently serves the fitness/personal training market. The business needs to expand into domestic cleaning services, requiring a dedicated marketplace where clients can book cleaning packages (Standard or Deep Clean) with configurable add-ons, recurring scheduling options, and transparent pricing with an escrow-based payment flow.

---

## Solution

A domestic cleaning booking system integrated into the existing SER platform, supporting:

- **Two-tier service packages**: Standard Clean (6h, max 2 bathrooms) and Deep Clean (8h, max 2 bathrooms)
- **Time-capped sessions** (max 8h per provider) with automatic overflow detection and sequential booking suggestions
- **Add-on system** for extended services (extra bathrooms, oven cleaning, laundry, etc.)
- **Dynamic pricing engine** calculating per-session costs: `Ptotal = (Ppackage + ΣA) + (Dkm × R)` with a 15/85 revenue split
- **Three scheduling modes**: Once-off, weekly (Mon–Sat), and fortnightly
- **Recurring template system** allowing per-session add-on variation
- **Provider-side upgrade workflow** — cleaner recommends Deep Clean after inspection, client accepts and pays difference via app
- **Escrow payment model** — funds held until client digital sign-off
- **60-minute no-show grace period** before refund triggers
- **Equipment disclaimer** surfaced in UI

---

## User Stories

### Booking Configuration

1. As a client, I want to select between Standard Clean and Deep Clean packages, so that I choose the service tier matching my property's needs.
2. As a client, I want to specify the number of bathrooms (1–5+), so that the system accurately prices and schedules my clean.
3. As a client, I want the system to automatically add "Extra Bathroom" add-on when I select 3+ bathrooms, so that I don't under-configure my booking.
4. As a client, I want to add optional add-ons (Interior Fridge Clean, Window Polish, Oven Deep Clean, Inside Cabinets, Wall Washing, Patio/Balcony Scrub, Rug Cleaning, Laundry, Ironing), each priced per unit, so that I customize my clean.
5. As a client, I want to see the calculated duration of my selected package + add-ons, so that I understand how long the session will take.
6. As a client, I want the system to prevent bookings exceeding 8 hours for a single provider, offering sequential booking as an alternative, so that labor regulations are respected.
7. As a client, I want to choose a booking frequency (once-off, weekly, or fortnightly) at checkout, so that I set up a schedule that suits my needs.
8. As a client, I want to select specific days of the week (Monday through Saturday) for recurring bookings, so that I align the service with my availability.
9. As a client, I want my recurring bookings to support per-session add-on variation (e.g., first Monday of month includes oven clean), so that I only pay for what I need each time.
10. As a client, I want to pick a single calendar date for once-off bookings, so that I schedule a one-time clean.

### Pricing & Payment

11. As a client, I want to see the itemized price breakdown before confirming (base package + add-ons + transport deposit), so that I understand what I'm paying for.
12. As a client, I want my payment to be held in escrow and only released to the cleaner after I provide digital sign-off, so that I have recourse if the service is unsatisfactory.
13. As a client, I want to pay a transport deposit calculated as `Dkm × R4.80`, so that cleaners are compensated for travel.
14. As a client, I want to see the equipment disclaimer stating I must provide detergents and cleaning equipment, so that I am properly prepared.

### Provider Workflow

15. As a service provider, I want to receive booking notifications with full details (package, add-ons, address, client instructions), so that I can prepare and arrive on time.
16. As a service provider, I want to trigger an in-app upgrade request from Standard to Deep Clean after inspecting the property, so that clients can approve and pay the price difference.
17. As a service provider, I want my payout to be calculated as `(Ppackage + ΣA) × 0.85 + Tdeposit`, so that I understand my take-home pay.
18. As a client, I want to receive a notification when my provider requests a Deep Clean upgrade, with the option to accept or decline, so that I decide whether to proceed.
19. As a client, when I accept the upgrade, I want to pay only the price difference via the app, so that the transition is seamless.

### Scheduling & Availability

20. As a client, I want Sundays to be unavailable by default, so that booking conflicts with provider rest days are reduced.
21. As a client, when my preferred provider is unavailable on a selected recurring date, I want to be prompted to choose a one-time replacement or skip that week, so that I can manage my schedule.
22. As a system, I want to cross-reference provider availability when a client selects a recurring day pattern, so that double-booking is prevented.

### Completion & Release

23. As a client, I want a digital sign-off button to confirm service completion, so that payment is released to the provider.
24. As a service provider, I want my appointment status to progress through: confirmed → in-transit → arrived → started → complete, so that the client can track my progress.
25. As the platform, I want to release 15% of `(Ppackage + ΣA)` to SER and 85% + transport deposit to the cleaner upon client sign-off, so that revenue split is automated.
26. As the platform, I want to allow a 60-minute grace period after the scheduled start time before processing a no-show refund, so that minor delays don't trigger premature refunds.

### Add-on Pricing

27. As a client, I want add-ons priced at `((duration_minutes / 60) × R33.27) × count`, so that pricing is transparent and proportional to time.
28. As a client, I want to see the duration in minutes for each add-on before confirming, so that I understand the time impact.

---

## Implementation Decisions

### Modules to Build / Modify

#### 1. `ServiceCategory` — Cleaning Category
- Extend or create a `cleaning` service category record in `service_categories`
- Flags: `is_active`, domain-specific metadata

#### 2. `Service` — Package Definitions
- Two package records: `DOM-STD` (Standard Clean) and `DOM-DEP` (Deep Clean)
- Fields: `base_price`, `max_duration_minutes`, `bathroom_cap`, `break_duration_minutes` (30 for standard, 60 for deep)
- Standard Clean base time: 6h (360 min). Deep Clean base time: 8h (480 min).

#### 3. `ServiceAddon` — Add-on Definitions
- Records for each add-on: Extra Bathroom (Standard 45 min, Deep 60 min), Interior Fridge (30 min), Interior Window (30 min), Oven Deep (60 min), Inside Cabinets (120 min), Wall Washing (60 min), Patio/Balcony (90 min), Rug Cleaning (90 min), Laundry (10 min/basket), Ironing (30 min/basket)
- Price formula: `((duration_minutes / 60) × 33.27) × count`
- `countable` flag to distinguish per-unit vs per-room vs per-load add-ons

#### 4. `ProviderService` — Provider Cleaning Offerings
- Link providers to the cleaning category with their service zone (km radius)
- `transport_rate` per provider (default R4.80/km)

#### 5. `Booking` — Extensions for Cleaning
- Extend existing `Booking` model or create `CleaningBooking` subclass
- New fields: `package_type` (standard/deep), `bathroom_count`, `scheduling_mode` (once_off/weekly/fortnightly), `recurring_day_mask` (bitmap Mon–Sat), `start_date`, `projected_duration_minutes`, `distance_km`, `transport_deposit`
- Status: `pending_payment`, `paid_escrow`, `confirmed`, `in_transit`, `arrived`, `in_progress`, `sign_off_pending`, `completed`, `cancelled`, `no_show`

#### 6. `BookingAddon` — Cleaning Add-on Attachments
- Existing `booking_addons` table — confirm it supports countable add-ons with `quantity`
- Link to `service_addons` with `count` field (e.g., 2 fridges, 3 bathrooms)

#### 7. `RecurringTemplate` / `RecurringTaskSet`
- New table storing the recurring schedule template: `client_id`, `provider_id`, `package_type`, `scheduling_mode`, `day_mask`, `start_date`, `is_active`
- `RecurringSession` child table: each generated session instance with its own date, package config, add-ons, and calculated price
- Each session priced independently — no flat subscription billing

#### 8. `UpgradeRequest` — Deep Clean Upgrade Workflow
- New table: `upgrade_requests` with `booking_id`, `provider_id`, `requested_at`, `client_id`, `status` (pending/accepted/declined), `price_difference`, `paid_at`
- On `status = accepted`: update booking `package_type` to deep and trigger additional payment collection

#### 9. `ClientSignOff` — Digital Sign-off
- New table: `sign_offs` with `booking_id`, `client_id`, `signed_at`, `photo_evidence_url` (optional)
- Single button in client UI — on press, release escrow and update booking status to `completed`

#### 10. `Payment` — Escrow Accounting
- Existing payments model extended: `escrow_status` (held/released/refunded), `ser_commission`, `cleaner_payout`, `transport_deposit`
- On sign-off: split released — 15% to SER ledger, 85% + transport to provider ledger
- No-show flow: after 60 min grace, trigger `escrow_status = refunded`

#### 11. `PricingEngine` — Service
- `calculateSessionPrice(package_type, addons[], distance_km)` → `{ subtotal, transport_deposit, total, ser_commission, cleaner_payout }`
- `calculateRecurringWeekPrice(template_id)` — sum session prices for the week
- Formula: `Ptotal = (Ppackage + ΣA) + (Dkm × 4.80)`
- Split: `ser = (Ppackage + ΣA) × 0.15`, `cleaner = (Ppackage + ΣA) × 0.85 + Tdeposit`

#### 12. `TimeValidationService` — Service
- `validateSessionDuration(package_type, addon_total_minutes)` → `{ valid: bool, projected_minutes, capped_at_8h }`
- If `projected_minutes > 480` (8h): return `{ valid: false, overflow_minutes, suggestion: sequential_booking }`

#### 13. `AvailabilityService` — Service
- `checkProviderAvailability(provider_id, day_mask, start_date, scheduling_mode)` → `{ available: bool, conflicts[] }`
- `getNextAvailableDates(provider_id, day_mask, count)` → dates[]

#### 14. `BookingController` — Endpoints
- `POST /api/cleaning/book` — create booking with package + add-ons + scheduling
- `POST /api/cleaning/booking/{id}/upgrade` — provider triggers upgrade request
- `POST /api/cleaning/upgrade/{id}/accept` — client accepts upgrade, pays difference
- `POST /api/cleaning/booking/{id}/sign-off` — client digital sign-off
- `POST /api/cleaning/booking/{id}/no-show` — trigger no-show flow after grace period

#### 15. Provider Availability Calendar
- `ProviderAvailability` model: `provider_id`, `day_of_week` (0–6), `available` (bool), `start_time`, `end_time`
- Sunday locked by default; provider can override
- Provider can set blocked dates (vacation, etc.)

### Key Interfaces

```php
// Pricing
calculateSessionPrice(string $packageType, array $addons, float $distanceKm): SessionPricing
// Returns: { subtotal, transportDeposit, total, serCommission, cleanerPayout }

// Time validation
validateSessionDuration(string $packageType, int $addonMinutes): DurationValidation
// Returns: { valid, projectedMinutes, overflowMinutes, suggestSequentialBooking }

// Availability
checkAvailability(int $providerId, string $dayMask, string $startDate, string $schedulingMode): AvailabilityResult
// Returns: { available, conflicts[], alternativeProviderIds[] }

// Recurring price calculation
calculateRecurringWeekPrice(int $templateId): Money
// Sums individual session prices for the upcoming week template
```

### Schema Changes

| Table | Change |
|-------|--------|
| `service_categories` | Insert `cleaning` category |
| `services` | Insert `DOM-STD`, `DOM-DEP` records |
| `service_addons` | Insert all cleaning add-on records |
| `provider_services` | Insert cleaning offerings per provider |
| `bookings` | Add cleaning-specific columns |
| `booking_addons` | Ensure `quantity`/`count` support per-unit add-ons |
| `recurring_templates` | New — recurring schedule definitions |
| `recurring_sessions` | New — individual session instances |
| `upgrade_requests` | New — Deep Clean upgrade workflow |
| `sign_offs` | New — client digital sign-off records |
| `provider_availability` | New — availability grid per provider |

---

## Testing Decisions

### What Makes a Good Test

- **Test external behavior only** — do not test internal implementation details
- **Use domain language** — package types, add-on names, pricing formulas as inputs/outputs
- **Test pricing calculations end-to-end** — verify the full formula `Ptotal = (Ppackage + ΣA) + (Dkm × 4.80)` with known inputs
- **Test revenue split** — verify 85/15 split with transport deposit added to cleaner payout
- **Test time cap enforcement** — verify that a 6h package + 150 min of add-ons triggers overflow validation

### Modules to Test

1. **PricingEngine** — highest priority
   - Standard package pricing (all room tiers)
   - Deep package pricing (all room tiers)
   - Add-on pricing with quantity multipliers
   - Revenue split calculations
   - Transport deposit calculation

2. **TimeValidationService**
   - Under 8h → valid
   - Exactly 8h → valid
   - Over 8h → invalid with overflow minutes
   - Break duration inclusion (30 min standard, 60 min deep)

3. **RecurringTemplate** — session generation
   - Weekly template generates correct number of sessions
   - Fortnightly generates sessions on 14-day cadence
   - Per-session add-on variation pricing

4. **AvailabilityService**
   - Provider booked on Monday returns conflict
   - Sunday blocked by default
   - Alternative provider suggestion on conflict

5. **UpgradeRequest Workflow**
   - Provider triggers upgrade → creates pending request
   - Client accepts → payment collected, package updated to deep
   - Client declines → booking remains standard, liability waiver note added

6. **Escrow Release on Sign-off**
   - Sign-off triggers correct fund split
   - No-show grace period expiry triggers refund

### Prior Art

- The existing `ServiceRequest.calculateFitnessTotal()` and `getFitnessPriceBreakdown()` in `app/Models/User/ServiceRequest.php` provide a pattern for the pricing engine.
- The existing `Booking` model and `BookingAddon` provide the parent structure for cleaning-specific extensions.

---

## Out of Scope

- Provider dispatch and routing optimization
- Equipment/consumables inventory management
- Client rating and review system
- Cleaning supplies marketplace
- Provider payroll and accounting exports
- Third-party payment gateway integration (payments assumed to flow through existing SER payment infrastructure)
- Liability waiver generation and e-signature (noted in flow but signature system not specified)

---

## Further Notes

- The add-on rate of R33.27/hour is flagged for future revision — the pricing engine should accept this as a configurable rate parameter, not a hard-coded constant.
- The 6-hour baseline for Standard/Deep duration in the spec is a simplification; the actual base_duration per room tier from the pricing table should be used for time projections. This means the 6h is a minimum, not a fixed value — the actual session duration scales with property size.
- Bathroom auto-addon logic (3+ bathrooms) must fire before time validation, so that the forced addon contributes to projected duration.
- The spec uses South African Rand (R) throughout — currency handling should respect the ZAR context.