# Booking Google Apps Script bridge

This Web App writes public booking requests to the separate `Booking` spreadsheet, tab `SmartFormat`. It refuses to write if the visible header row is not the exact 19-column Windows-import contract.

## Script properties

Set these in Apps Script → Project settings → Script properties (the spreadsheet ID below is the dedicated clean `Booking` file):

- `SPREADSHEET_ID`: `19MdwTn3P9PScwK_3l7QA1oCFh0HWs1T5hwba8b1fUP8`
- `SHEET_NAME`: `SmartFormat`
- `SHARED_SECRET`: a new random value; use the same value only in dashboard `.env` as `BOOKING_SHEET_SHARED_SECRET`

Run `setupBookingSheet()` once, authorise the script, then deploy as a Web App that executes as the deploying account and is accessible to anyone. Put the resulting `/exec` URL in dashboard `.env` as `BOOKING_SHEET_WEBHOOK_URL`.

The shared secret is never stored in the Sheet or committed to Git. `submission_uuid` idempotency is held in row developer metadata, so no extra visible column is added.
