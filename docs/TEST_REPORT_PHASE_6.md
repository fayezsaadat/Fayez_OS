# Test report — Fayez OS 0.6.0 / Phase 6

Executed **28 September 2026** with PHP **8.4.26**, MariaDB **11.8.6**,
Chromium **153.0.8010.12**, Python Playwright/requests. Actual PHP/MySQL auth,
IndexedDB transactions, Web Locks and Web Crypto are used, except the explicitly
identified system-notification stubs below. Disposable profiles/databases and
synthetic records/files are not shipped as user data. Historical Phase 5 and 4
reports remain `TEST_REPORT_PHASE_5.md` / `TEST_REPORT_PHASE_4.md`.

## New local workflows — PASS

`tests/phase6_browser.py`:

- Saved-data search over real local collections, Persian/Arabic letters/digits,
  type filter, escaped result preview, QR payload exclusion and no script execution.
- Real encrypted download from Backup UI; ciphertext does not contain the fixture's
  private plaintext. The complete 19-store schema is populated except reserved
  empty outbox. Actual file bytes and newer localStorage draft/preference values
  survive export, validation and destructive local restore.
- Wrong passphrase reports failure. A changed baseline and another active workspace
  tab prevent replacement. Successful restore bumps entity version and retains
  exact Unicode file content. Preview itself does not replace records.
- Simulated crash window: durable epoch committed while stale localStorage cache
  remains. Reload discards stale cache instead of resurrecting old data.
- Thirteen malformed/archive rejection cases: ciphertext tamper, truncation,
  plaintext, wrong scope, future format, missing store, unsafe ID, invalid exact
  money/date/checklist, incorrect file hash, nonempty outbox and unknown preference.
- Persian PIN, incorrect PIN, concealed shell, cold-start gate, reload/unlock,
  second-tab verification without relocking the first tab, cooldown and verified
  disable. Local data survives PIN operations.
- Atomic concurrent receipt dedup, deletion without renewed delivery, overnight
  quiet hours and actual opt-in due-today task reminder in the foreground center.
- **54 combinations:** Search/Controls/Backup × dark/light × widths
  320, 360, 375, 390, 412, 430, 768, 1024, 1440: no document/app horizontal overflow.
- Cached cold offline reload and navigation of all three new modules; zero uncaught
  browser exceptions in the suite. Final tested shell cache is **v6.0.1**.

## Limits, interruption and device lifecycle — PASS with declared simulation

`tests/phase6_edges.py`:

- **Five actual 10 MiB files / 50 MiB total** encrypted, decrypted, validated and
  restored; byte length/content checked. This is not a metadata-only quota test.
- Injected IndexedDB transaction abort leaves all stores unchanged; retry succeeds.
  This tests rollback, **not physical disk exhaustion or power failure**.
- Real browser denied-notification-permission path reports unavailability honestly.
  This sandbox's Chromium denies native delivery even with automation permission
  grants. Therefore payload generic-text defaults, explicit private-title preview
  and close-on-lock behavior were additionally tested with **explicit browser API
  stubs**. No real OS popup, OS lock screen, click or mobile notification is claimed.
- A real synthetic MediaStream's tracks end when PIN lock destroys the QR controller.
  Not a physical camera test. Accelerated browser clock verifies idle lock;
  simulated visibility event verifies hidden-page lock.
- Damaged PIN configuration fails closed; removing only its documented verifier key
  recovers the UI without deleting the local database. Zero uncaught exceptions.

## Account security and portability — PASS

`tests/phase6_api.py` uses real PHP/MariaDB:

- Revoke route method, CSRF, account guard and incorrect-current-password failures.
- Correct password advances the epoch, rotates current SID/CSRF, preserves current
  access and rejects an independently authenticated second session on its next call.

`tests/phase6_account.py`:

- Real same-account login in two browser profiles; encrypted account archive transfers
  and restores local notes to the second profile. Guest rejects that account archive.
- Fresh-account explicit Guest import with no existing Guest DB returns zero and
  does not leave an empty/partial v4 database behind.

Registration throttling was encountered while re-running disposable tests; only the
isolated QA rate-limit files were reset. Production limits/code were not weakened.

## Earlier functionality rerun against Phase 6 — PASS

- `browser_smoke.py`: 15 home launchers, 20 drawer entries, app filtering, every
  working launcher, back behavior, notifications, preferences, slash → global Find,
  dark/light/system and offline reload across nine viewport widths. Assertions were
  updated for the deliberate drawer-count and global-search changes.
- `phase2_browser.py`: 26 arithmetic results, 14 invalid/domain/injection inputs;
  Calculator, Notes/checklists/drafts, Tasks, Calendar CRUD, stale-editor handling,
  foreground reminders, offline writes and eight-width editor/layout checks.
- `phase3_browser.py`: 55 pure money/validation assertions, exact ledgers/budgets/
  CSV, currency/manual reciprocal and dated synthetic provider cache, contacts/photo
  handling, optimistic conflicts, offline behavior, dark/light nine-width layouts,
  100-entry conversion cap; zero uncaught exceptions.
- `phase4_browser.py`: weather/news fixture-based UI and cache, QR generation/real
  image decoding/synthetic-camera cleanup, local atomic file bytes/rename/delete,
  offline use, both themes/nine widths and 14 additional validation/quota checks;
  zero uncaught exceptions. No new live provider availability claim is made here.
- `storage_browser.py`: v1/v2/v3 → v4 data-preserving migrations, full 19-store schema,
  blocked-storage honest error and calculator operation without saved history.
- `phase5_api.py`: actual SQL auth, ownership, snapshot CAS/validation, private-file
  bytes/rename/delete, password/session revocation and protected paths.
- `phase5_browser.py`: real registration/separate scopes, Guest import, second-device
  manual transfer, local/cloud races, offline edits, private files, profile/password,
  cross-tab logout and account isolation; dark/light nine-width account/cloud/security
  forms and zero uncaught exceptions.
- `api_smoke.py`: version 0.6.0 / phase 6 health, default-disabled currency adapter,
  query/method rejection, JSON/no-store/nosniff and eight protected paths.

Earlier Phase 5 maximum server-file/count/throttle results are historical; the full
`phase5_limits.py` suite was not rerun for this release. Server limits are unchanged.

## Static/package checks

- **51 JavaScript modules**, **31 PHP files**: syntax checks passed.
- **65** service-worker precache paths exist; API/personal data are not in that list.
- Relative documentation links checked. Archive format, limits, recovery and full
  server-backup procedures documented; no runtime credentials, live SQL, provider
  cache, user uploads or test-generated backups belong in the release ZIP.
- Release packaging is checked for CRC, source-byte equality and private-file
  exclusions. Previous delivered Phase 5 ZIP is preserved unchanged.

## Not verified / not promised

No physical Android/iOS/WebView, native notification display/click, signed APK/AAB,
Android Keystore, Windows Apache/XAMPP, MySQL 8, PHP 8.0 specifically, real disk-full/
power-loss scenario, load benchmark or independent security/cryptographic audit.
Tests of clock/visibility/camera interruptions use the simulations identified above.

No background push/closed-app reminder scheduler, email password recovery, encrypted
live IndexedDB, arbitrary-client concurrency protection, unbounded archives or
automatic/server-wide backup. PIN is a bypassable UI privacy curtain. Source ZIP,
manual six-store cloud snapshot, local encrypted archive and full host backup are
four different artifacts. Production deployment must review HTTPS/cookie/CSP/file
permissions and provider licensing, then rehearse an operator server recovery.

## Reproduce (optional development tools only)

Use an isolated PHP/SQL server at the project root and disposable browser contexts;
normal XAMPP users do not need Python, Playwright or Node. Set `FAYEZ_URL` to its root
(e.g. `http://127.0.0.1:8081/`). Install Playwright/Chromium and requests separately.
Run the named Python suites individually. `FAYEZ_SHOTS` can direct Phase 6 screenshots
and the synthetic encrypted download into a private QA directory. Never run fixtures
against production users, a personal browser profile or live private file storage.
