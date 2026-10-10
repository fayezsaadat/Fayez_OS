# Test report — Fayez OS 0.5.0 / Phase 5

Executed **28 September 2026** on PHP **8.4.26**, **MariaDB 11.8.6**, Chromium
**153.0.8010.12**, Python Playwright/requests. The database-backed tests use actual
PHP/SQL endpoints, not mocked identity/storage. Temporary synthetic users, records,
photos and files are not release seeds. Earlier Phase 4 results are retained in
`TEST_REPORT_PHASE_4.md` as historical context.

## Phase 5 API — PASS

`tests/phase5_api.py` verifies:

- Real registration, distinct public IDs, SID rotation, password login and profile
  updates. Cookie/no-store behavior. Anonymous private reads rejected.
- Invalid/missing CSRF blocked; mismatched account guard blocked. Server owner comes
  from session, never body. Unexpected fields/methods rejected.
- Exact Unicode workspace persistence and retrieval from SQL; another account gets
  revision zero, not someone else's data. Stale revision returns 409 without overwrite.
- Duplicate IDs, extra stores, malformed nested data and owner injection rejected.
- Real multipart private file upload; byte-for-byte authenticated download with
  attachment/nosniff/sandbox headers. Another account cannot list, read, rename or
  delete that file. Unsupported/executable/SVG/fake-image/path/empty uploads fail.
- Versioned rename, stale rename conflict, same-extension enforcement, delete and
  subsequent missing-file response.
- Incorrect current password blocked; password change revokes an independently
  authenticated second session. Logout blocks further private requests; old password
  fails and new password works.

## All six stores and real quotas — PASS

`tests/phase5_limits.py` round-trips all six real payload types together through
MySQL-compatible storage: checklist note, task, calendar event/timezone, TRY exact
minor-unit transaction, zero AFN budget and Persian contact.

Rejects associative/malformed checklists, impossible dates, invalid event duration,
non-numeric money, prototype-shaped category, remote avatar URL, >1,000 records and
>4 MiB JSON. Failed attempts leave the previous revision unchanged.

Five actual 10 MiB files fill the account's **50 MiB** quota; another byte fails
without extra metadata. A file over **10 MiB** fails. Separately, **100 tiny files**
fill the count quota and the 101st fails. Deletion restores the measured quota.

Repeated incorrect logins reach **429 + Retry-After**; duplicate registration
attempts also reach the registration limiter. Existing authenticated workspace
reads still succeed. These are functional limiter checks, not an abuse/load test.

## Phase 5 browser integration — PASS

`tests/phase5_browser.py` uses isolated browser profiles and the real backend:

- Create a guest note and light appearance, register account A, and verify its
  empty separate database/default theme. Explicitly copy guest data, refuse a second
  import over existing records, then upload a real cloud revision.
- A second independent device/browser logs in, starts with no automatic imported
  records, confirms cloud replacement and renders the actual note in Notes.
- Device B uploads revision 2; device A's stale revision 1 push is blocked. A local
  edit during pull confirmation aborts replacement rather than losing the edit.
  Successful replacement raises record versions and prevents an old editor save.
- Signed-in offline reload and real local editing; cloud actions report a connection
  failure, not fabricated sync success. Reconnection restores account controls.
- Real UTF-8 cloud upload, exact downloaded bytes, rename, unsupported SVG rejection,
  confirmed cloud delete. New controls remain locked during list/transfer work;
  the test waits for the actual enabled upload input, not just DOM attachment.
- Name/profile and password update; another device's old session returns to Guest.
  Cross-tab logout restores the original guest note/theme. Account B sees neither
  A's local records/preferences nor A's cloud workspace/files.
- Populated Workspace/Cloud files/Profile & security at **320, 360, 375, 390, 412,
  430, 768, 1024 and 1440 px**, both dark and light: no horizontal overflow. Selected
  actual screenshots visually reviewed. **Zero uncaught browser errors.**

Separate real checks verify the production-flag `Secure; HttpOnly; SameSite=Lax`
cookie header and the embedded-preview message/link actually opening a new tab.
No weakening of SameSite is used to accommodate the preview iframe.

## Migration — PASS

The **actual Phase 4 ZIP's SQL schema** was imported into a separate disposable
legacy database, a legacy user inserted, then `database/migrate-phase5.php` run
**twice**. Name and password_hash were retained; a random 32-hex public ID and
session epoch 1 were added. Schema still contains **18 tables**. The migrator also
ran successfully on the new schema. It requires an administrator/ALTER connection,
not the restricted runtime user's credentials.

`tests/storage_browser.py` again passes original guest v1/v2/populated-v3 → v4
migration, preserving old note/task/event/ledger/budget/contact/rate/draft values.
Guest remains v4/19 stores, and blocked-storage cases still fail honestly.

## Existing regressions — PASS again under Phase 5

- `browser_smoke.py`: shell, **15 home/17 drawer entries**, search/categories,
  original launchers/previews, Back, theme/settings/reload, API, keyboard and offline.
- `phase2_browser.py`: parser's 26 result/14 invalid cases; notes/tasks/calendar
  CRUD, drafts, version conflicts, local notices, offline writes and eight widths.
- `phase3_browser.py`: 55 pure money/model assertions, exact ledgers/budgets/CSV,
  currency history/quote/input race, contact photos/drafts/two-tab conflicts,
  offline writes and 18 width/theme combinations.
- `phase4_browser.py`: weather/news fixture flows, local QR generation/raster and
  synthetic-camera decode/cleanup, files atomic Blob CRUD/quota, offline use,
  18 width/theme combinations and 14 additional security/validation checks.
- `api_smoke.py`, `phase4_api_smoke.py`: current health, default-disabled currency,
  weather/news query/method rejection, protected paths and active CSP/permissions.
- Provider normalization suites: **23 currency + 25 weather/RSS** fixture assertions.
  Fresh upstream availability is not implied by these fixture reruns; Phase 4's
  actual network checks are documented in the historical report.

The restrictive CSP remained active in browser tests (no bypassCSP/unsafe-eval).
The supplied Apache policy additionally denies cross-origin framing; that directive
is intentionally omitted in the development CLI router for Arena preview hosting.

## Static / packaging

**30 PHP files** passed lint. **42 JS files** passed module syntax/import checks.
**55 unique precache assets** cover every application JS/CSS module. Manifest/config
JSON, local HTML asset links and Markdown links validated. Final worker **v5.0.1**.
ZIP verification checks CRC, required files, source-byte equality and exclusion of
runtime caches, limiter buckets, uploaded content, SQL dumps and credentials.

## Reproduce safely

Use a **disposable database/server/profile**, not production. The tests create
accounts, exercise real file storage and quota limits, and intentionally use many
auth attempts. A shared limiter may reject repeated suites: wait for its window or
use a fresh isolated test limiter directory. Never clear real production buckets
or user data to make tests pass.

```sh
python -m pip install playwright requests
python -m playwright install chromium
# Linux may need: python -m playwright install-deps chromium
# Import SQL / configure DB_* and private storage first. Development server:
APP_ENV=development php -d upload_max_filesize=10M -d post_max_size=12M \
  -S 127.0.0.1:8081 -t . dev-router.php
# Another terminal, same disposable environment:
export FAYEZ_URL=http://127.0.0.1:8081/
python tests/phase5_api.py
python tests/phase5_browser.py
python tests/phase5_limits.py
python tests/browser_smoke.py
python tests/phase2_browser.py
python tests/phase3_browser.py
python tests/phase4_browser.py
python tests/storage_browser.py
python tests/api_smoke.py
python tests/phase4_api_smoke.py
php tests/provider_test.php
php tests/phase4_provider_test.php
```

`FAYEZ_SHOTS` optionally saves browser screenshots. Test dependencies are not
application runtime requirements. Native MySQL runtime tests do not require Node.

## Not claimed / open deployment gates

No direct Windows XAMPP/Apache, MySQL 8 or PHP 8.0 run; compatibility is targeted,
not verified on every runtime. No real-phone camera, Android/iOS/WebView cookies,
Safari/Firefox, signed APK/AAB or full assistive-technology audit.

No adversarial penetration or load certification, distributed concurrency test,
system crash/power-loss simulation, full filesystem/SQL recovery test or complete
cookie behavior matrix. Sequential two-device CAS and transactional race checks
are not a multi-host stress test.

No email verification/recovery, native lock/encryption, remote offline-cache erase,
full backup/version history, automatic merge, malware scan, account-erasure UI or
production operational backup service. Review hosting, TLS/cookie/session storage,
reverse-proxy rate limits, data retention and provider rights before public use.
