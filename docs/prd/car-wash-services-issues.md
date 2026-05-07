# SER Car Wash Services — Issues

## Issue 1: Tier Model Foundation
**Status:** Pending
**Blocked by:** —
**Description:** Washer model with tier flag (Essential/Pro Tech), Equipment checklist at onboarding with all required items for Pro Tech, manual admin approval for Pro Tech activation, automatic Essential activation, self-requested downgrade pathway, auto-demotion when re-verification fails checklist. Re-verification triggers: annual, post-dispute, post-low-rating.

---

## Issue 2: Package & Pricing Data Structure
**Status:** Pending
**Blocked by:** 1
**Description:** Seed data for 6 standard package names (Wash & Go, Wash & Dry, Wash Dry & Tyre/Vac, Interior Only, Standard Wash, Full Valet) and 3 car types (Hatchback/Sedan, SUV/4x4, Mini-Bus/Kombi). Price range validation per tier (Essential ranges and Pro Tech floors). Per-car-type pricing for all add-ons. Revenue split: 85% washer / 15% SER applied to full booking amount.

---

## Issue 3: Washer Profiles
**Status:** Pending
**Blocked by:** 1
**Description:** Public washer profile page showing: profile photo, tier badge (Essential/Pro Tech), actual prices for all packages and add-ons, ratings and reviews, equipment list or tier badge. Profile visible to clients before booking.

---

## Issue 4: Client Booking Flow
**Status:** Pending
**Blocked by:** 1
**Description:** Client browsing and selection: filter washers by tier (Essential/Pro Tech), list/map view with prices, ratings, and profiles visible before booking. Client selects tier, car type (dropdown: Hatchback/Sedan, SUV/4x4, Mini-Bus/Kombi), location. Washer sees address and can decline. 24-hour advance booking minimum, 1-hour gap between multiple daily bookings. Washer manages own availability calendar; client books only within declared windows.

---

## Issue 5: Payment
**Status:** Pending
**Blocked by:** 1, 4
**Description:** Upfront payment collection at booking confirmation. SER takes 15% immediately. Washer payout released after 24-hour client dispute window expires. SER as payment intermediary — no cash/manual payments.

---

## Issue 6: Dispute Resolution
**Status:** Pending
**Blocked by:** 4
**Description:** Client can raise dispute within 24h of job completion. Dispute types: job quality, car type misclassification, equipment mismatch, damages. SER investigates and determines outcome (refund, partial refund, payment to washer). Washer bears financial liability. Disputes also trigger equipment re-verification for the washer.