# DrB SEO reporting: disabled integration slice

This adds a separate Persian RTL SEO navigation item and reporting client to the
canonical staff client. It does not replace clinical reports. It renders validated
canonical snapshots, but the backend transport remains **hard-disabled**. Normal
use displays the truthful unconfigured state. Missing data is never shown as zero.

## Current behavior

- Staff navigation appears only after the existing `/auth/me` response confirms
  the `admin` or `super_admin` role. Mobile retains the existing five destinations
  with the SEO link in the same horizontally scrollable bottom bar.
- `GET /api/v1/analytics/seo` uses the existing bearer authentication and
  `analytics.view` permission, plus positive staff/clinic identity and an explicit
  owner/admin check. No client-supplied clinic/project/site is accepted.
- The route is hard-disabled: authorized valid requests always return HTTP503,
  `data:null`, and `meta.code=reporting_unconfigured`. There is no environment
  activation switch, network client, provider credential or database access.
- No-store middleware runs before authentication, including denials. The browser
  uses no-store, omits cookies and rejects redirects. Its bearer can reach only
  the existing HTTPS app/dashboard CRM origins.
- The page shows connection status and provider descriptions, with working retry,
  range, comparison and closing-date controls. Valid canonical snapshots produce
  GSC KPI cards and per-section metric tables with source/provenance, data dates,
  each metric's coverage completeness and exact interval (including when comparison is disabled),
  freshness and guarded previous-period comparisons. Only granted sections that
  occur in the response are rendered. Raw errors and unknown fields are discarded.
- Nulls remain absent; unknown/unavailable/no_data states hide contradictory
  metrics. Partial, degraded, mismatched definitions/windows or missing coverage
  never imply growth. A zero baseline never produces infinite percentage change.
- The presentation parser enforces the canonical schema/envelope and requested
  period; invalid or tampered payloads render no metrics. This is a presentation
  safety boundary, not the backend's future tenant-authorization implementation.
- SEO-page logout uses the existing canonical staff token and revocation route,
  clears only staff state immediately, aborts report reads and bounds revocation
  to8s. Network failure does not prove server revocation. Shared chrome identity
  HTML escaping is corrected to prevent an inherited markup-injection sink.

## Agreed next integration contract

MS Robot owns a separate, currently disabled HTTP facade:
`GET /api/v1/reporting/snapshot`.

Query: `period=last_7d|last_14d|last_28d|last_30d|last_90d`,
`comparison=previous|none`, optional real, nonfuture UTC `endDate=YYYY-MM-DD`.
Every requested and derived date must remain in years0001–9999; previous-window
arithmetic cannot underflow. No arbitrary modern-year cutoff is imposed.
Defaults are last_28d and previous. No caller-controlled identity or scope fields.
The upstream rejects duplicate/unknown keys. CRM's parsed query rejects unknown
keys/arrays, but its existing Request parser collapses duplicate scalar keys;
upstream raw-query parity belongs to the live integration gate.

Success is the unchanged `ms-robot.reporting.v1` snapshot, including period and
comparison sections, source/provenance, data dates, coverage, freshness and ETag.
HTTP ETag is weak (`W/` plus the canonical tag); the JSON snapshot's `etag` remains
the strong quoted SHA256 semantic tag. This client currently uses no-store reads
and does not attempt conditional caching.304 requires fresh identity, exact mapping and current grants. Upstream errors:
400 invalid_request, 401 unauthorized, indistinguishable404 not_found,
405 method_not_allowed and503 reporting_unconfigured/reporting_unavailable.
The local CRM error envelope is intentionally distinct from the upstream wire
format; it is not a second analytics data schema.

Before live wiring: review S2S authentication and credential lifecycle; establish authoritative positive
clinic-to-project/site binding and current client-only report-section grants;
complete applicable MS Robot AAX-128/AAX-55 gates; validate both ends and rollback.
Never infer ownership from domain spelling, forward the CRM/browser bearer,
duplicate provider ingestion, or enable provider jobs through this page.
The current upstream ledger materializes GSC totals. GA4 sections are contract-ready
but must remain absent/unavailable until actually connected. Daily query/page
tables are a separate upstream server function with no HTTP table endpoint yet;
this slice does not invent that endpoint, time series, charts or query rankings.

## Source and overlap

Prepared against Medical-CRM main `6e5e6fa9daf2faa3571043c7df237fdb3b8a4a3c`.
Only existing `Frontend/assets/js/chrome.js` and `config/routes.analytics.php`
are changed. Existing analytics.html, AnalyticsController.php, shared/api.js,
public/index.php and all booking/security PR files remain unchanged.
No credentials, patient data, production snapshots or archive files were read.

## Verification and limits

- `node --test tests/seo-client.test.mjs tests/seo-snapshot.test.mjs`:22 checks.
- With jsdom26.1.0 available: `node --experimental-vm-modules --test tests/seo-dom.test.cjs`:10 checks. These are DOM behavior checks, not visual browser QA. JSDOM does not implement full navigation; logout assertions cover token clearing and one revocation request, not actual browser navigation.
- `php -n tests/seo-backend.php`: standalone synthetic checks. In this workspace,
  executed with official WordPress @php-wasm/node3.1.56 using PHP8.3.33:52 checks.
  No native PHP is installed here; production reports PHP8.3.35. This does not
  validate production extensions, web-SAPI headers, DB-backed auth/RBAC or routing.
- JS syntax and `git diff --check` pass.
- Real browser QA remains blocked: local Chromium cannot create required sockets;
  the cloud browser rejected the local URL with ERR_BLOCKED_BY_CLIENT. Mobile
  geometry, actual font layout and full browser interaction remain unverified.
- No production deploy, integration activation, provider request, public push or
  PR is included. The analytics feature is not complete.
