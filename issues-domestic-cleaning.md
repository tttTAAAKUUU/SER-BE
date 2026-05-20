# Domestic Cleaning Marketplace — Issue Breakdown

## ISSUE 1: Data Layer — Cleaning Service Catalogue

**Context:** The platform needs cleaning-specific service categories, packages, and add-ons before any booking can be made. All pricing and duration logic derives from these records.

**Vertical slice:** Tracer-bullet that starts at the database and surfaces in the API — a client can query available cleaning packages and see their prices.

**Tasks:**
1. Insert `cleaning` record into `service_categories`
2. Insert `DOM-STD` (Standard Clean) and `DOM-DEP` (Deep Clean) records into `services` with fields: `base_price`, `max_duration_minutes`, `bathroom_cap`, `break_duration_minutes`, `package_type` (standard/deep)
3. Insert all cleaning add-on records into `service_addons` with: name, description, `duration_minutes`, `price_formula` (rate-based), `countable` flag, `addon_category` (standard/deep)
   - Extra Bathroom Standard (45 min), Extra Bathroom Deep (60 min)
   - Interior Fridge Clean (30 min)
   - Interior Window Polish (30 min)
   - Oven Deep Clean (60 min)
   - Inside Kitchen Cabinets (120 min)
   - Wall Washing (60 min)
   - Patio & Balcony Scrub (90 min)
   - Rug Cleaning (90 min)
   - Laundry Wash & Hang (10 min/basket)
   - Ironing & Folding (30 min/basket)
4. Add endpoint `GET /api/cleaning/packages` returning available cleaning packages with pricing
5. Add endpoint `GET /api/cleaning/addons` returning all available add-ons with durations and per-unit prices

**Testable:**
- `GET /api/cleaning/packages` returns Standard and Deep packages with correct prices
- `GET /api/cleaning/addons` returns all 11 add-ons with durations
- Price calculation: `((30/60)*33.27) = R16.64` for Interior Fridge

---

## ISSUE 2: Pricing Engine — Session Price & Revenue Split

**Context:** All pricing derives from this engine. It must correctly compute `Ptotal`, `Tdeposit`, SER commission, and cleaner payout for every booking. This is the financial core — errors here have direct monetary consequences.

**Tasks:**
1. Create `App\Services\Cleaning\PricingEngine`
   - `calculateSessionPrice(packageType, addons[], distanceKm): array` returning `{ subtotal, transportDeposit, total, serCommission, cleanerPayout }`
   - Formula: `subtotal = Ppackage + ΣA`, `transportDeposit = Dkm × 4.80`, `total = subtotal + transportDeposit`
   - Split: `serCommission = subtotal × 0.15`, `cleanerPayout = (subtotal × 0.85) + transportDeposit`
2. Make add-on rate configurable (`addon_rate` config key) rather than hard-coded — flag for future revision
3. Build `GET /api/cleaning/price-preview` endpoint: accepts `{ package_type, addon_ids[], bathroom_count, distance_km }` and returns itemized price breakdown
4. Add unit tests for PricingEngine covering:
   - Standard package 1-room (R270) + 1 fridge add-on → correct total
   - Deep package 2-room (R460) + oven + laundry → correct total
   - Revenue split: verify 85/15 split
   - Transport: R4.80/km × 10km = R48.00 deposit
   - Cleaner payout = (subtotal × 0.85) + transportDeposit

**Testable:**
- Input: Standard 1-room (R270) + 2 fridges (2 × R16.64 = R33.28) + 0km → total R303.28, SER commission R40.50, cleaner R262.78
- Input: Deep 2-room (R460) + oven (R33.27) + 10km → total R544.57, transport R48, cleaner = (493.27 × 0.85) + 48 = R467.28, SER = R73.99

---

## ISSUE 3: Time Validation Service — 8-Hour Cap Enforcement

**Context:** Labor regulations require a hard cap of 8 hours per provider per session. The system must calculate projected duration and block/redirect bookings that exceed the limit before they are confirmed.

**Dependency:** Requires Issue 1 (add-on durations are stored in add-on records).

**Tasks:**
1. Create `App\Services\Cleaning\TimeValidationService`
   - `validateSessionDuration(packageType, addons[], bathroomCount): array` returning `{ valid, projectedMinutes, overflowMinutes, suggestion }`
   - Base duration: Standard = 360 min + 30 min break = 390 min billable window; Deep = 480 min + 60 min break = 540 min
   - Add-on durations summed from add-on records
   - Auto-addon logic: if `bathroomCount > 2`, inject Extra Bathroom add-on before calculating
   - Hard cap: 480 min (8h). If `projectedMinutes > 480`: return invalid with overflow minutes and `suggestion: sequential_booking`
2. Integrate into booking creation flow — return validation error before payment step if over cap
3. Add unit tests:
   - Standard (360+30) + 45 min add-ons = 435 min → valid
   - Standard + 150 min add-ons = 510 min → invalid, overflow 30 min
   - 3 bathrooms auto-adds 45 min → 435 + 45 = 480 min → valid (at exact cap)
   - 3 bathrooms auto-adds 45 min + another 60 min add-on → 540 min → invalid, overflow 60 min

**Testable:**
- POST booking with projected 510 min returns 422 error with `overflow_minutes: 30` and `suggestion: sequential_booking`
- POST booking with 3 bathrooms auto-adds Extra Bathroom before time check

---

## ISSUE 4: Booking Model — Cleaning Booking Extension

**Context:** The existing `Booking` model needs cleaning-specific fields to support the full cleaning workflow. This is the central record that ties together pricing, scheduling, status, and payment.

**Tasks:**
1. Run migration adding columns to `bookings` table:
   - `package_type` — enum (standard, deep), nullable
   - `bathroom_count` — tinyint unsigned, default 1
   - `scheduling_mode` — enum (once_off, weekly, fortnightly)
   - `recurring_day_mask` — tinyint unsigned (bitmap Mon=1, Tue=2, Wed=4, Thu=8, Fri=16, Sat=32)
   - `projected_duration_minutes` — int unsigned
   - `distance_km` — decimal(8,2)
   - `transport_deposit` — decimal(10,2)
   - `ser_commission` — decimal(10,2)
   - `cleaner_payout` — decimal(10,2)
   - `sign_off_at` — timestamp nullable
   - `no_show_grace_started_at` — timestamp nullable
2. Update `Booking` model: cast `package_type`, `scheduling_mode`, `recurring_day_mask`, add relationships
3. Confirm `booking_addons` table has `quantity`/`count` column for countable add-ons
4. Add `BookingAddon` relationship to `Booking` with `count` pivot

**Testable:**
- `Booking::create([...])` with `package_type: 'deep', bathroom_count: 3` stores correctly
- `Booking::addons()` returns collection with `count` pivot value

---

## ISSUE 5: Booking API — Create, Confirm, Cancel

**Context:** Clients need API endpoints to configure and submit cleaning bookings with package selection, add-on selection, bathroom count, and scheduling options.

**Dependencies:** Requires Issue 1 (packages/add-ons), Issue 2 (pricing), Issue 4 (model).

**Tasks:**
1. `POST /api/cleaning/book` — create booking
   - Input: `{ provider_id, package_type, bathroom_count, addons: [{id, count}], scheduling_mode, recurring_day_mask, start_date, distance_km }`
   - Runs TimeValidationService first — rejects if > 8h
   - Calculates pricing via PricingEngine
   - Creates Booking + BookingAddon records
   - Returns booking summary with itemized price breakdown
2. `POST /api/cleaning/booking/{id}/confirm-payment` — moves booking to `paid_escrow` status, triggers payment hold
3. `POST /api/cleaning/booking/{id}/cancel` — cancels booking, triggers refund if already paid
4. `GET /api/cleaning/booking/{id}` — returns full booking details with add-ons and pricing breakdown
5. `GET /api/cleaning/bookings` — list client's cleaning bookings

**Testable:**
- Full round-trip: create booking → get price breakdown → confirm payment → see booking in list
- Booking with 3 bathrooms auto-adds Extra Bathroom before pricing
- Over-8h booking returns 422 before creation

---

## ISSUE 6: Recurring Booking — Templates & Sessions

**Context:** Clients need weekly and fortnightly recurring schedules where each session can have different add-on configurations and is priced individually. Not a subscription model — each session is treated as a standalone template.

**Dependencies:** Requires Issue 4 (booking model), Issue 5 (booking API base).

**Tasks:**
1. Migration for `recurring_templates` table: `id, client_id, provider_id, package_type, scheduling_mode, recurring_day_mask, start_date, is_active, created_at, updated_at`
2. Migration for `recurring_sessions` table: `id, template_id, scheduled_date, package_type, projected_duration_minutes, status (pending/confirmed/completed/skipped), created_at`
3. `RecurringTemplate` model with `sessions()` relationship
4. `RecurringSession` model with `template()` relationship
5. On creation of recurring booking (scheduling_mode weekly/fortnightly):
   - Create `RecurringTemplate` record
   - Generate `RecurringSession` records for next 8 weeks
   - Each session links to a `Booking` record
6. `GET /api/cleaning/templates/{id}` — returns template with all sessions and itemized pricing per session
7. `GET /api/cleaning/templates/{id}/week-price` — sums prices of all sessions in the upcoming week
8. PATCH endpoint to update add-ons for a specific session: `PATCH /api/cleaning/session/{id}` — re-prices that session individually

**Testable:**
- Weekly template with Monday + Wednesday generates 16 sessions over 8 weeks
- Fortnightly template generates sessions on 14-day cadence
- Updating add-ons on session 3 only changes that session's price, not others
- `week-price` sums all sessions for the upcoming week

---

## ISSUE 7: Provider Availability — Calendar & Conflict Detection

**Context:** When a client sets up a recurring booking (e.g., every Monday), the system must cross-reference the provider's availability and prevent double-booking. Sundays are blocked by default.

**Dependencies:** Requires Issue 4 (booking model).

**Tasks:**
1. Migration for `provider_availability` table: `id, provider_id, day_of_week (0=Sun–6=Sat), available (bool), start_time, end_time`
2. `ProviderAvailability` model with `provider()` relationship
3. Seed Sunday as unavailable for all providers by default
4. `POST /api/provider/{id}/availability` — provider sets their weekly availability grid
5. `GET /api/provider/{id}/availability` — returns weekly availability grid
6. `AvailabilityService.checkProviderAvailability(providerId, dayMask, startDate, schedulingMode): AvailabilityResult`
   - Returns `{ available: bool, conflicts: [], alternativeProviderIds: [] }`
   - When scheduling_mode is weekly and dayMask includes a day, check all future instances against provider's grid
   - If conflict found: return the conflicting date(s) and suggest alternative providers
7. Integrate availability check into booking creation flow (Issue 5)

**Testable:**
- Provider with Monday unavailable → weekly Monday booking returns conflict
- Sunday always returns unavailable unless provider has manually overridden
- Conflicting date returned in `conflicts` array with date and time

---

## ISSUE 8: Upgrade Request Workflow — Standard → Deep

**Context:** On-site, a cleaner may determine the property needs Deep Clean instead of Standard. The provider triggers an upgrade request, the client approves via app, pays the difference, and the booking package_type is updated.

**Dependencies:** Requires Issue 4 (booking model), Issue 5 (booking API).

**Tasks:**
1. Migration for `upgrade_requests` table: `id, booking_id, provider_id, client_id, status (pending/accepted/declined), price_difference, requested_at, responded_at, paid_at`
2. `UpgradeRequest` model
3. `POST /api/cleaning/booking/{id}/request-upgrade` — provider triggers upgrade request
   - Calculates price difference: Deep package price − Standard package price (add-ons unchanged)
   - Creates `UpgradeRequest` with `status: pending`
   - Notifies client (in-app notification — notification system not built here, interface only)
4. `POST /api/cleaning/upgrade/{id}/accept` — client accepts
   - Updates `UpgradeRequest.status` to `accepted` and `paid_at`
   - Updates `Booking.package_type` to `deep`
   - Triggers additional payment collection for price difference
5. `POST /api/cleaning/upgrade/{id}/decline` — client declines
   - Updates `UpgradeRequest.status` to `declined`
   - Adds note to booking: `liability_waiver_applied: true`
6. `GET /api/cleaning/booking/{id}/upgrade-request` — returns current upgrade request status if exists

**Testable:**
- Provider calls `request-upgrade` on a Standard booking → creates pending UpgradeRequest with correct price_difference
- Client accepts → booking.package_type becomes `deep`, payment collected
- Client declines → booking stays Standard, waiver flag set

---

## ISSUE 9: Client Digital Sign-off — Escrow Release

**Context:** Payment is held in escrow until the client provides digital sign-off. On sign-off, funds are released: 15% to SER, 85% + transport to cleaner. No-show after 60-minute grace also triggers refund.

**Dependencies:** Requires Issue 4 (booking model), Issue 2 (pricing engine).

**Tasks:**
1. Migration for `sign_offs` table: `id, booking_id, client_id, signed_at, photo_evidence_url` nullable
2. `SignOff` model with `booking()` relationship
3. `POST /api/cleaning/booking/{id}/sign-off` — client confirms service completion
   - Creates `SignOff` record with `signed_at`
   - Releases escrow: splits funds — 15% to SER ledger, 85% + transport deposit to provider ledger
   - Updates booking status to `completed`
4. `POST /api/cleaning/booking/{id}/start-no-show-clock` — starts the 60-min grace period
   - Sets `booking.no_show_grace_started_at = now()`
5. Background job (or scheduled check): after 60 min from `no_show_grace_started_at`, if booking not signed off → trigger refund
   - Updates `booking.status` to `no_show`
   - Releases escrow as refund
6. `GET /api/cleaning/booking/{id}/status` — returns current status including whether sign-off is pending

**Testable:**
- Client signs off → `sign_offs` record created, booking status `completed`, SER + cleaner ledgers updated
- After 60-min grace with no sign-off → refund triggered, booking status `no_show`
- Sign-off only allowed when booking status is `in_progress` or `sign_off_pending`

---

## ISSUE 10: Booking Status Progression & Provider Notifications

**Context:** The booking lifecycle runs through: confirmed → in_transit → arrived → started → sign_off_pending → completed. Providers and clients need status change notifications.

**Dependencies:** Requires Issue 4 (booking model), Issue 5 (booking API).

**Tasks:**
1. Add appointment progression logic to `Booking` model:
   - `markInTransit()` — sets status `in_transit`
   - `markArrived()` — sets status `arrived`
   - `markStarted()` — sets status `in_progress` and records `started_at`
   - `markSignOffPending()` — sets status `sign_off_pending` and triggers client notification
   - `markCompleted()` — delegates to sign-off flow (Issue 9)
2. `POST /api/cleaning/booking/{id}/status` — provider updates status
   - Input: `{ status: in_transit | arrived | started | sign_off_pending }`
   - Validates transition is legal (no skipping)
3. Provider notification on booking creation (in-app notification interface — hook point, not full notification system)

**Testable:**
- Provider marks arrived → status becomes `arrived`
- Provider attempts to skip from `confirmed` to `started` → returns 422 illegal transition
- Status change triggers notification payload to client

---

## ISSUE 11: Equipment Disclaimer & UI Surface Area

**Context:** The spec mandates that UI explicitly state clients must provide all detergents and cleaning equipment. This disclaimer must appear at checkout before payment confirmation.

**Dependencies:** Requires Issue 5 (booking API), Issue 2 (pricing engine for price preview).

**Tasks:**
1. Add `equipment_disclaimer` text field to booking summary response (API surface)
2. Ensure frontend/consumer API consumers surface this text prominently at checkout
3. Content: *"Please note: Clients must provide all necessary detergents, cleaning equipment, and supplies. The service provider will arrive with only professional tools."*
4. Include in `GET /api/cleaning/price-preview` response as `disclaimer` field

**Testable:**
- `GET /api/cleaning/price-preview` returns `disclaimer` field with correct text
- Disclaimer is non-empty and appears before payment step

---

## ISSUE 12: Provider Cleaning Profile — Transport Rate & Service Zone

**Context:** Each cleaning provider needs a transport rate (default R4.80/km) and a service zone so the system can calculate transport deposits and validate whether a client's address is within range.

**Dependencies:** Requires Issue 1 (service category), Issue 4 (booking model).

**Tasks:**
1. Migration to `provider_service_profiles` or extend `provider_services`: `transport_rate` (decimal, default 4.80), `service_radius_km` (decimal)
2. Update provider onboarding to capture cleaning service zone and transport rate
3. `GET /api/cleaning/providers?lat=X&lng=Y` — list providers who service the given coordinates within their radius
4. Integrate distance calculation into booking flow (Issue 5) — use provider's `transport_rate`, not hard-coded R4.80

**Testable:**
- Provider with `transport_rate: 5.50` and `service_radius_km: 25` — booking with client 15km away uses R5.50/km
- Provider with `service_radius_km: 20` rejects booking from client 25km away with appropriate error

---

## Cross-Cutting Concerns

**Database transactions:** All multi-record operations (booking creation, sign-off, recurring template generation) must run inside a database transaction.

**Configuration:** The add-on rate (R33.27/hr) and transport rate default (R4.80/km) must be in a config file, not hard-coded in service classes. Add a `config/cleaning.php` config file.

**ZAR currency:** All money fields stored as integers (cents) or decimal(10,2) in ZAR. No floating-point math on money.
---

## ISSUE: OTP System for Email Verification

**Context:** The service provider registration flow (frontend PRD at `docs/prd/registration-flow.md`) includes email verification via 6-digit OTP. The backend needs endpoints to send, verify, and manage OTPs with proper rate limiting and cooldown periods.

**Vertical slice:** End-to-end OTP flow — generate OTP on send request, validate on verify request, enforce resend cooldown. Surfaces in the API as two public endpoints and a cache layer.

**Tasks:**
1. Create `POST /api/auth/send-otp` endpoint accepting `{ email }`
   - Validate email format
   - Generate 6-digit numeric OTP
   - Store in cache with key `otp:{email}` with 300s (5min) TTL
   - Queue email job to send OTP (interface with existing mailer)
   - Return success response immediately
2. Create `POST /api/auth/verify-otp` endpoint accepting `{ email, otp }`
   - Retrieve OTP from cache by email key
   - Compare provided OTP with stored value (constant-time comparison)
   - Return `{ valid: true }` on match, `{ valid: false, message: 'Invalid or expired OTP' }` on mismatch
   - On success, delete OTP from cache (one-time use)
3. Enforce resend cooldown: if `send-otp` called within 60s of last send for same email, return `{ error: 'Please wait before requesting a new OTP' }` with remaining seconds
4. Add request validation using Laravel Form Request classes
5. Add unit tests covering: valid OTP passes, invalid OTP fails, expired OTP fails, resend cooldown blocks, email validation

**Testable:**
- `POST /api/auth/send-otp { email: "sp@example.com" }` → 200, OTP stored in cache
- `POST /api/auth/verify-otp { email: "sp@example.com", otp: "123456" }` → valid/invalid based on stored value
- Rapid resend within 60s → 429 with cooldown remaining

**Blocked by:** None — can start immediately

---

## ISSUE: Service Area & Category in Registration

**Context:** The registration flow (Step 1: Account Setup, Step 3: Domain Selection) requires collecting service area and service type during registration. Currently `POST /api/service-providers/register` accepts basic fields but lacks service area and category selection. Also need to validate email/phone uniqueness at registration time (not just at field level).

**Vertical slice:** Extended registration payload and service category relationship — a service provider can register with their service area and select which service categories (car_wash, domestic_cleaning, fitness, etc.) they offer.

**Tasks:**
1. Run migration adding `service_area` column to `service_provider_profiles` table (string, nullable)
2. Create `service_categories` table if not exists (id, name, slug, description, icon, is_active)
3. Create pivot table `service_provider_service_category` (service_provider_profile_id, service_category_id)
4. Update `StoreServiceProviderProfileRequest` validation:
   - Add `service_area` string validation
   - Add `service_category_ids` array of existing category IDs
   - Ensure email/phone uniqueness checks hit database (case-insensitive)
5. Update `UserRegistrationService::registerServiceProvider()` to:
   - Save `service_area` to profile
   - Attach selected service categories to pivot
6. Add `GET /api/service-categories` public endpoint returning all active categories
7. Add unit tests covering: registration with service_area saves correctly, registration with category_ids creates pivot records, duplicate email returns 422

**Testable:**
- `POST /api/service-providers/register` with `service_area: "Cape Town"` → profile has service_area
- Registration with valid `service_category_ids: [1, 2]` → pivot records created
- `GET /api/service-categories` → returns list of active categories

**Blocked by:** None — can start immediately

---

## ISSUE: Profile Photo Upload for Registration

**Context:** Registration Step 4 (Profile Identity) requires uploading a profile photo during the flow, not just after registration via `PUT /service-providers/profile`. Need a dedicated upload endpoint that can associate the photo with an in-progress registration session.

**Vertical slice:** Profile photo upload endpoint — accepts image upload, stores file, returns URL for association with registration.

**Tasks:**
1. Create `POST /api/uploads/profile-photo` endpoint (public, no auth required for registration flow)
   - Accept multipart form data with `photo` file field
   - Validate: image only (jpg, jpeg, png), max 5MB
   - Store in `storage/app/public/profile_images/` with unique filename
   - Return `{ url: "/storage/profile_images/{filename}" }`
2. Add `OtpVerification` model and migration to track verification state
3. Add unit tests for file validation (rejects non-image, rejects >5MB, accepts valid jpg/png)

**Testable:**
- `POST /api/uploads/profile-photo` with valid image → 200 with URL
- Invalid file type → 422 with validation error
- File > 5MB → 422 with validation error

**Blocked by:** None — can start immediately (parallel with slice 2)

---

## ISSUE: KYC Document Upload

**Context:** Registration Step 5 (KYC Identity Verification) requires uploading ID documents (front and back). Need an endpoint to accept and store these documents with proper validation.

**Vertical slice:** KYC document upload — accepts ID front/back images, stores files, returns URLs for association with provider profile.

**Tasks:**
1. Create `POST /api/uploads/kyc-documents` endpoint (public, no auth)
   - Accept multipart form data with `id_front` and `id_back` file fields
   - Validate: image or PDF (jpg, jpeg, png, pdf), max 10MB each
   - Store in `storage/app/public/kyc_documents/` with unique naming (e.g., `{user_id}_id_front_{timestamp}.ext`)
   - Return `{ id_front_url, id_back_url }`
2. Add `kyc_status` column to `service_provider_profiles` table (enum: pending, submitted, verified, rejected), default pending
3. Add `id_number` column to `service_provider_profiles` table (string, nullable)
4. Add validation for ID number format (South African ID 13-digit format or passport)
5. Add unit tests for file validation, ID number format validation

**Testable:**
- `POST /api/uploads/kyc-documents` with both files → 200 with URLs
- Missing `id_front` → 422
- Non-PDF/image file → 422
- File > 10MB → 422

**Blocked by:** None — can start immediately (parallel with slice 2)

---

## ISSUE: Registration Completion & Auto-Authentication

**Context:** Registration Step 6 (Completion) needs to finalize the registration, mark the provider as verified (or pending verification for KYC), trigger the welcome email, and return an authentication token so the frontend can log the provider in automatically without a separate login step.

**Vertical slice:** Registration completion — receives the final payload with all step data, completes registration record, issues Sanctum token, returns auth response.

**Tasks:**
1. Create `POST /api/service-providers/complete-registration` endpoint
   - Accept full registration payload: `{ registration_token, step_data }` where step_data contains all steps 1-5 collected data
   - Validate all step data (reuse validation schemas from steps)
   - Complete the registration:
     - Mark registration complete
     - Set KYC status to 'submitted'
     - Generate and issue Sanctum auth token
   - Trigger welcome email job
   - Return auth token response: `{ user, token, token_type: "Bearer" }`
2. Create `RegistrationSession` model to store intermediate registration state keyed by registration token
3. Update `send-otp` to optionally associate OTP with a registration session
4. Add unit tests for complete registration flow

**Testable:**
- Valid completion payload with all steps → 200 with auth token
- Missing required step data → 422
- Invalid registration token → 404

**Blocked by:** Slice 1 (OTP), Slice 2 (Service Area/Category), Slice 3 (Profile Photo), Slice 4 (KYC Documents)

---

## ISSUE: Registration Session Persistence

**Context:** Registration flow spans 6 steps. If a user refreshes mid-way or navigates away, their progress should be preserved. The backend needs to store in-progress registration data keyed by a registration token passed from the frontend.

**Vertical slice:** Registration session storage — save intermediate state per step, allow frontend to resume with token.

**Tasks:**
1. Create `POST /api/service-providers/registration/session` endpoint
   - Accept `{ registration_token, step, data }` payload
   - Upsert registration session record by token
   - Store step number and accumulated data as JSON
   - Return `{ registration_token, saved_step, saved_data }`
2. Create `GET /api/service-providers/registration/session/{token}` endpoint
   - Return current saved state for given token (step number, all accumulated data)
   - Return 404 if session not found or expired (24h expiry)
3. Create `RegistrationSession` model and migration
   - Fields: token (unique), step (int), data (json), expires_at, created_at, updated_at
4. Add unit tests for session save/retrieve, expiry handling

**Testable:**
- `POST` with step 1 data → session saved
- `GET` existing token → returns saved state
- `GET` expired or non-existent token → 404

**Blocked by:** Slice 2 (Service Area/Category)
