# Architecture Decision Record
## Freight Marketplace – Honduras (MVP)

Companion to the MVP Implementation Guide. Captures the decisions made during architecture planning (2026-08-04), with rationale and implementation notes.

---

## 1. Decisions at a Glance

| # | Area | Decision |
|---|------|----------|
| 1 | User modeling | Single `users` table, dual roles allowed (spatie/laravel-permission) |
| 2 | Shipment lifecycle | PHP backed enum + transition guards inside Actions + status history table |
| 3 | Geospatial | PostGIS from day one (`geography(Point,4326)` + GiST), plus seeded city catalog for UX |
| 4 | Quote concurrency | `DB::transaction()` + `lockForUpdate()` in `AcceptQuoteAction` |
| 5 | Mobile auth | Sanctum personal access tokens; Google via native sign-in → ID-token exchange |
| 6 | Real-time | Reverb web-only (quotes page, shipment status); Flutter uses FCM + pull-to-refresh |
| 7 | Files | spatie/laravel-medialibrary (photos) + dedicated `documents` table (verification docs) |
| 8 | API | `/api/v1` prefix from day one, Eloquent API Resources, consistent error envelope |
| 9 | Commission | **Free during validation** (0%), with shadow commission ledger |
| 10 | Deployment | DO Droplets + Laravel Forge; managed PG/Redis/Spaces |
| 11 | Testing | Pest, feature-first, against real Postgres (PostGIS rules out SQLite) |
| 12 | Repos | Two repos: `backend` (Laravel + Inertia + Filament) and `mobile` (Flutter) |
| 13 | Spatial package | matanyadaev/laravel-eloquent-spatial |
| 14 | Notification fan-out | Region + truck type targeted, chunked, dedicated queue |
| 15 | Observability | Sentry (Laravel + Flutter) + Laravel Pulse + DO uptime alerts |
| 16 | Web scaffold | Official Laravel 12 Vue starter kit (Inertia v2, Vue 3, TS, Tailwind v4) |

---

## 2. Key Implementation Notes

### Users & Roles (D1)
- One `users` table; roles via spatie/laravel-permission (`customer`, `transporter`, `admin`).
- Role-specific data in `companies` (customer/business) and `transporter_profiles`.
- A user may hold both roles; UI offers a context switch. Sanctum token abilities scoped per role where needed.

### Shipment State Machine (D2)
- `ShipmentStatus` backed enum with `canTransitionTo(ShipmentStatus $to): bool`.
- Every transition happens inside a dedicated Action (`PublishShipmentAction`, `AcceptQuoteAction`, `MarkPickedUpAction`, …) which validates the transition and appends to `shipment_status_histories` (status, actor, timestamp, notes).
- Exit states `Cancelled` / `Expired` reachable only from allowed states; `Expired` set by a scheduled command.

### PostGIS (D3, D13)
- Migration: `CREATE EXTENSION IF NOT EXISTS postgis;` (supported on DO Managed Postgres).
- `shipments.origin` / `shipments.destination` as `geography(Point, 4326)`, GiST indexed. Same for transporter operating regions (point + radius, or polygon later).
- Return-trip matching: `ST_DWithin(candidate.origin, my.destination, :radius_m)` AND `ST_DWithin(candidate.destination, my.origin, :radius_m)` AND pickup date within 24–72h window.
- Seeded `cities` table (SPS, TGU, La Ceiba, Choluteca, …) for autocomplete and corridor analytics — coordinates power matching, city names power humans.
- Eloquent bridge: `matanyadaev/laravel-eloquent-spatial` (Point casts, `whereDistance`, `orderByDistance`). Revisit clickbar/laravel-magellan if Phase 3 route-intersection logic materializes.

### Quote Acceptance (D4)
```
AcceptQuoteAction:
  DB::transaction:
    $shipment = Shipment::lockForUpdate()->find(...)
    abort_unless($shipment->status === ShipmentStatus::Quoted)
    accept chosen quote, assign transporter
    bulk-close competing quotes
    record status history
  after commit: dispatch QuoteAccepted event (notifications, broadcast)
```
Concurrent accepts block on the row lock, then fail the status re-check cleanly.

### Auth (D5)
- Web: session auth (starter kit) + Socialite redirect flow for Google.
- Flutter: native Google Sign-In → `POST /api/v1/auth/google` with ID token → Laravel verifies against Google certs → mints Sanctum PAT → stored in `flutter_secure_storage`. Email/password login mints tokens the same way.
- Future OTP login stays open (add provider later; no architectural blocker).

### Real-time (D6)
- Reverb + Laravel Echo on web only: private channels `shipments.{id}` (status) and customer quotes page (new quote arrives live — supports the "<30 min to first quote" metric feeling real).
- Flutter: FCM push (`laravel-notification-channels/fcm`) + pull-to-refresh. No websocket client in the MVP app.

### Files & Documents (D7)
- Media (truck/cargo/POD photos): medialibrary collections on Spaces (S3 driver), conversions for thumbnails, CDN via Cloudflare.
- Verification documents (license, national ID, insurance): dedicated `documents` table — `type`, `status (pending/approved/rejected)`, `expires_at`, `reviewed_by`, private disk, temporary signed URLs. Reviewed via Filament workflow.

### Commission (D9) — metric change
- MVP charges **0%**. The success metric "Commission Collection > 95%" is **dropped from the MVP scorecard**.
- Still write a `commissions` row per completed shipment (base amount, rate 0%, computed fee) — a shadow ledger that makes Phase 2 pricing data-driven and shows would-be revenue per transporter.

### Notification Fan-out (D14)
- `ShipmentPublished` → queued listener → one spatial query: transporters whose operating region covers origin AND truck type matches AND available.
- Chunked `Notification::send()` on a dedicated `notifications` queue so fan-outs never delay transactional notifications (quote accepted, etc.).
- Watch metric: shipments receiving zero notifications (thin-supply signal). If real, add the hybrid escalation (widen radius / relax truck type after 30–60 min without quotes).

### Deployment (D10)
- Forge-provisioned Droplet(s): nginx + PHP 8.4-FPM, daemons for Horizon and Reverb, scheduler cron, push-to-deploy.
- Managed: PostgreSQL (with PostGIS), Redis, Spaces. Cloudflare in front. Start single droplet; LB + second droplet when traffic justifies.

### Testing (D11)
- Pest, feature-first, real Postgres in CI (PostGIS ⇒ no SQLite).
- Priority order: every shipment state transition (legal and illegal), quote-accept race path, policies/authorization, API contract tests for Flutter-consumed endpoints. Unit tests only for pure logic (e.g., enum transition map).

---

## 3. Package Lists

### Composer (beyond Laravel 12 defaults)
- `laravel/sanctum` — API tokens for Flutter
- `laravel/horizon` — queue dashboard/supervision
- `laravel/reverb` — websockets (web-only scope)
- `laravel/socialite` — Google login (web redirect flow)
- `spatie/laravel-permission` — roles
- `spatie/laravel-medialibrary` — photos on Spaces
- `matanyadaev/laravel-eloquent-spatial` — PostGIS integration
- `laravel-notification-channels/fcm` — push notifications
- `filament/filament` — admin panel
- `laravel/pulse` — app health dashboard
- `sentry/sentry-laravel` — error tracking
- Dev: `pestphp/pest`, `laravel/pint`, `larastan/larastan`, `barryvdh/laravel-ide-helper`

### NPM (beyond starter kit: Inertia v2, Vue 3, TS, Tailwind v4, Vite)
- `pinia` — client state (notifications badge, user context)
- `laravel-echo` + `pusher-js` — Reverb client
- `@sentry/vue` — web error tracking (optional, low cost)

### Flutter (as per MVP guide, confirmed)
- `go_router`, `dio`, `flutter_secure_storage`, `flutter_riverpod`, `firebase_messaging`, `google_maps_flutter`, `geolocator`, `image_picker`, `cached_network_image`
- Add: `google_sign_in` (ID-token exchange), `sentry_flutter` (crash reporting)

---

## 4. Open Items (deliberately deferred)
- OTP/phone login provider selection (Twilio vs regional SMS gateway) — Phase 2.
- Hybrid notification escalation job — build only if zero-quote shipments prove common.
- clickbar/laravel-magellan migration — only if Phase 3 route logic outgrows eloquent-spatial.
- Payments provider for Phase 2 (informs commission ledger settlement fields).
