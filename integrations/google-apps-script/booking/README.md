# Booking Google Apps Script bridge

This Web App writes public booking requests to the separate `Booking` spreadsheet, tab `SmartFormat`. It refuses to write if the visible header row is not the exact 19-column Windows-import contract.

## Script properties

Set these in Apps Script → Project settings → Script properties:

- `SPREADSHEET_ID`: `1vmAbQjBu8jDXV-oZ2iCSFQ9nVqCNFrTecNPt1E1O9Lc`
- `SHEET_NAME`: `SmartFormat`
- `SHARED_SECRET`: a new random value; use the same value only in dashboard `.env` as `BOOKING_SHEET_SHARED_SECRET`

Run `setupBookingSheet()` once, authorise the script, then deploy as a Web App that executes as the deploying account and is accessible to anyone. Put the resulting `/exec` URL in dashboard `.env` as `BOOKING_SHEET_WEBHOOK_URL`.

The shared secret is never stored in the Sheet or committed to Git. `submission_uuid` idempotency is held in row developer metadata, so no extra visible column is added.
