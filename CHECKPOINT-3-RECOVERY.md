# Checkpoint 3 recovery

Reconstructed against public `main` after the original Checkpoint-3 ZIP was lost. Migrations 018–020 were never published; recovered files resume at **021** and **022**.

## Behaviour restored

- Appointment writes take a clinic-scoped MySQL `GET_LOCK('crm:appointments:{id}')` then re-check provider and room overlap **inside** the transaction.
- Occupying statuses are `scheduled` and `confirmed`. Cancelled/completed slots do not block new bookings.
- Reactivating a cancelled/completed appointment to `scheduled`/`confirmed` repeats the same conflict checks; a taken slot returns HTTP 409.
- Room collisions (same clinic + non-empty room + overlap) return HTTP 409.
- Malformed JSON request bodies return HTTP 400 (`بدنه درخواست JSON نامعتبر است`) instead of being treated as `[]`.
- Reminder dispatch already skipped cancelled/completed appointments; uniqueness remains `appointment_id + channel + offset`.
- Booking remains one intake per `submission_uuid`, at most one confirmation SMS.

## Operator notes

- Apply `021_harden_reminders_otp_and_intakes.sql` then `022_align_incremental_foreign_keys.sql` on a backup first.
- Duplicate-index errors on 021 mean that unique/index already exists — safe to skip that statement.
- Rotate every credential that ever lived in the public git tree (`.env`, `wp-config.php`). Deleting files on HEAD does **not** purge history. Take the repository private or rewrite history, then `chmod 600` runtime configs on the server.

No secrets belong in this file or in git.
