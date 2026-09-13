# Appointment booking v2 backend

MySQL is the authoritative booking store. Google Sheets and Google Calendar are projections/integrations and never own slot locking.

## Patient flow

1. WordPress booking intake verifies the mobile by OTP and creates/updates the patient identity.
2. The successful intake response may issue a patient-scoped session token.
3. `GET /api/v1/appointment-bookings/availability` returns concrete free slots and enabled payment gateways.
4. Authenticated patient `POST /api/v1/appointment-bookings/holds` atomically claims one slot.
5. `POST /api/v1/appointment-bookings/{id}/payment` creates an idempotent gateway attempt.
6. The public gateway callback verifies server-to-server and moves a live claim to `paid_pending_staff`. Failed payments release the claim immediately. Late successful payments after hold expiry remain paid but unreserved and require staff slot reconciliation.
7. Staff confirms the paid booking, creating the clinical appointment and Calendar outbox event.

## Staff/admin API

- `POST /api/v1/admin/appointment-bookings` — receptionist/admin creates free or gateway-backed booking; accepts existing `patient_id` or `mobile` + patient name.
- `GET /api/v1/admin/appointment-bookings` — booking/follow-up queue.
- `POST /api/v1/admin/appointment-bookings/{id}/reconcile-slot` — assign a new free slot to a paid booking whose original hold expired.
- `POST /api/v1/admin/appointment-bookings/{id}/confirm` — create the confirmed clinical appointment.
- `POST /api/v1/admin/appointment-bookings/{id}/followup-complete` — audit staff follow-up completion.
- `GET|POST /api/v1/admin/appointment-open-days` — list/configure clinic days, quota, hours and slot duration. Server enforces max two open days per ISO week and eight per calendar month.
- `GET /api/v1/admin/appointment-payments` — payment attempts without exposing gateway authority secrets.
- `POST /api/v1/admin/appointment-payments/{id}/reconcile` — re-query gateway verification state.
- `GET|PATCH /api/v1/admin/appointment-integrations` — super-admin status and write-only credential configuration.
- `POST /api/v1/admin/appointment-bookings/{id}/sheet-sync` — force one ScheduledVisits projection retry.

## ScheduledVisits Google Sheet

The `ScheduledVisits` tab is upserted by booking UUID. It records the scheduled time, source/channel, registrar, payment status/gateway/reference, confirmation state, follow-up state and Calendar event ID. `ScheduledVisitSheetService` retries pending/error rows; once Sheet writing is enabled it also backfills rows previously marked skipped.

Required Apps Script properties: `SPREADSHEET_ID`, `SHARED_SECRET`, and optionally `SCHEDULED_VISIT_SHEET_NAME` (defaults to `ScheduledVisits`). The repository Apps Script supports both the legacy booking-row append and ScheduledVisits upsert.

## Recurring worker

`bin/calendar-sync.php` independently performs:

- expired hold release,
- Calendar outbox push/pull when Calendar credentials are configured, and
- ScheduledVisits retry/backfill.

Calendar failure does not block Sheet sync, and Sheet failure does not block Calendar sync. Install `deploy/systemd/drb-appointment-sync.service` and `.timer`; the timer runs every two minutes.

## Required production configuration

- `BOOKING_APPOINTMENT_DEPOSIT_RIALS`
- `BOOKING_PAYMENT_GATEWAYS`
- `ZARINPAL_MERCHANT_ID` and/or `VANDAR_API_TOKEN`
- `BOOKING_SHEET_WEBHOOK_URL`, `BOOKING_SHEET_SHARED_SECRET`, `BOOKING_VISIT_SHEET_NAME`
- `GOOGLE_CALENDAR_ID`, `GOOGLE_CALENDAR_CLIENT_ID`, `GOOGLE_CALENDAR_CLIENT_SECRET`, `GOOGLE_CALENDAR_REFRESH_TOKEN`

Secrets remain in the private `.env`; read/status endpoints expose only configured/not-configured state.
