# SER Car Wash Services — Product Requirements Document

## 1. Overview

SER operates a two-tier car wash marketplace connecting clients with self-employed washers. The platform manages washer onboarding, tier assignment, booking, payment, and dispute resolution.

---

## 2. Tier Model

### 2.1 Tiers

There are two washer tiers:

| Tier | Description |
|------|-------------|
| SER Essential Washer | Manual wash — affordable, driveway-style maintenance |
| SER Pro Tech Washer | Advanced equipment — reduced scratch risk, deeper clean |

Tier assignment is a **single binary flag per washer**. A washer operates exclusively in one tier.

### 2.2 Toolbox Check (Onboarding)

Washers complete a **self-reported equipment checklist** at signup. The checklist contains specific equipment items (e.g., "I have a pressure washer: yes/no").

**Pro Tech activation:** Washer must check ALL Pro Tech equipment items. Submission triggers a **manual SER admin approval** before Pro Tech status is granted.

**Essential activation:** Automatic upon submission of Essential equipment checklist.

### 2.3 Tier Changes After Onboarding

| Scenario | Outcome |
|----------|---------|
| Washer requests downgrade from Pro Tech → Essential | SER updates tier; washer can now charge Essential prices |
| Washer re-verifies and is missing any Pro Tech equipment item | **Auto-demotion** to Essential |
| Washer re-verifies and completes all Pro Tech items | Remains Pro Tech (no admin re-approval required) |
| Annual re-certification due | Washer must re-submit toolbox check |
| Low rating trigger (below threshold) | SER re-validates equipment |
| Dispute finding of substandard equipment | SER re-validates equipment |

---

## 3. Service Categories

### 3.1 Standard Package Names

SER standardises package names across all washers. Package *scope* is washer-defined (washer defines what their "Standard Wash" includes), but the name is consistent.

**Standard packages:**

| Package Name |
|--------------|
| Wash & Go |
| Wash & Dry |
| Wash, Dry & Tyre/Vac |
| Interior Only |
| Standard Wash |
| Full Valet |

### 3.2 Car Types

Three vehicle categories:

- Hatchback / Sedan (Low Effort)
- SUV / 4x4 (Medium Effort)
- Mini-Bus / Kombi (High Effort)

---

## 4. Pricing

### 4.1 Pricing Model

- **Essential washers:** set prices within SER-specified ranges per package and car type
- **Pro Tech washers:** set prices above SER-specified price floors per package and car type
- **Add-ons:** washer-priced within tier-specific ranges

All prices shown on washer's public profile before booking.

### 4.2 Price Ranges — Essential

| Package Name | Hatchback/Sedan | SUV/4x4 | Mini-Bus/Kombi |
|---|---|---|---|
| Wash & Go | R50–R75 | R75–R100 | R100–R120 |
| Wash & Dry | R75–R100 | R100–R120 | R120–R140 |
| Wash, Dry & Tyre/Vac | R120–R130 | R140–R150 | R180–R200 |
| Interior Only | R80 | R100 | R140 |
| Standard Wash | R130–R150 | R160–R180 | R210–R230 |
| Full Valet | R250 | R320 | R450 |

**Essential Add-ons (priced per car type):**

| Add-on | Price Range |
|--------|-------------|
| Tyre Polish | R35–R60 |
| Vacuuming | R35–R60 |
| Pet Hair Removal | R50–R85 |
| Full Dash Wipe Only | R35–R60 |
| Clay Bar Treatment | R50–R90 |
| Rim and Wheel Cleaner | R80+ |

### 4.3 Price Floors — Pro Tech

| Package Name | Hatchback/Sedan | SUV/4x4 | Mini-Bus/Kombi |
|---|---|---|---|
| Wash & Go | R90 | R120 | R160 |
| Wash & Dry | R120 | R160 | R210 |
| Wash, Dry & Tyre/Vac | R190 | R240 | R310 |
| Interior Only | R150 | R200 | R280 |
| Standard Wash | R240 | R310 | R380 |
| Full Valet | R450 | R580 | R750 |

**Pro Tech Add-ons (price floors, per car type):**

| Add-on | Price Floor |
|--------|-------------|
| Tyre Polish | R40 |
| Vacuuming | R40 |
| Pet Hair Removal | R45 |
| Full Dash Wipe Only | R35 |
| Clay Bar Treatment | R50 |
| Engen Wash | R80 |
| Pet Removal | R40 |
| Full Interior Sanitation | R35 |
| Undercarriage Cleaning | R70 |
| Engen Bay Cleaning | R120 |
| Rim and Wheel Cleaner | R60 |
| Leather Conditioning/Treatment | R380 |
| Deodorization Treatment | R120 |

### 4.4 Revenue Split

85% to service provider, 15% to SER. Applied to the **full booking amount** (base package + all add-ons combined).

---

## 5. Washer Profiles

Full public profile visible to clients:

- Profile photo
- Tier badge (Essential / Pro Tech)
- Actual prices for all packages and add-ons
- Ratings and reviews
- Equipment list or tier badge implying equipment level

---

## 6. Client Booking Flow

### 6.1 Discovery and Selection

- Client browses a list/map of available washers filtered by tier preference (Essential or Pro Tech)
- Client sees all prices, ratings, and profiles before committing
- Client selects their tier (Essential or Pro Tech) at booking time

### 6.2 Booking Details

- **Car type:** selected by client from a dropdown (Hatchback/Sedan, SUV/4x4, Mini-Bus/Kombi). Washer can dispute misclassification after arrival.
- **Location:** client address is visible to washer at booking request. Washer can decline or renegotiate based on site conditions (e.g., no water access, underground parking).
- **Service area:** no SER-imposed radius restriction. Washer can travel anywhere.
- **Lead time:** minimum 24-hour advance booking required.
- **Multiple bookings:** washer can accept multiple bookings per day with a minimum 1-hour gap between jobs.
- **Availability:** washer manages their own calendar — sets available days/times; client can only book within declared windows.

---

## 7. Payment

- **Collection:** client pays in full at booking confirmation (upfront)
- **SER's cut:** 15% taken immediately at payment collection
- **Washer payout:** released after 24-hour client dispute window expires
- SER acts as payment intermediary; does not handle cash/manual payments between client and washer

---

## 8. Cancellation Policy

Uniform SER policy applied across all bookings (policy details to be defined at implementation).

---

## 9. Dispute Resolution

### 9.1 Trigger

Client raises a dispute within **24 hours of job completion**.

### 9.2 Scope of Disputes

- Job quality (substandard work, incomplete service)
- Misrepresentation of car type at booking
- Equipment mismatch (e.g., washer claimed Pro Tech but arrived with garden-hose equipment)
- Damages

### 9.3 Resolution

- SER investigates dispute
- If washer is found at fault: payment is withheld from washer; client refunded or partial refund determined by SER
- **Financial liability rests with the washer**, not SER

### 9.4 Enforcement Triggers

Washer equipment re-verification is triggered by:

1. Annual re-certification
2. Client dispute finding of quality issues
3. Washer rating dropping below SER-defined threshold

---

## 10. Extensibility

The two-tier model is designed to accommodate future tiers above Pro Tech (e.g., "SER Elite"). New tiers can be introduced without restructuring the existing model.

---

## 11. Out of Scope (To Be Defined Later)

-具体的 cancellation refund percentages and timelines
- Exact low-rating threshold for re-verification trigger
- SER admin review workflow details
-具体的 dispute resolution SLA