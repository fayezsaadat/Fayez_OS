# Folder structure — Phase 7 (historical tree plus additions below)

Put the whole `fayez-os` directory inside `C:\xampp\htdocs\`. Preserve relative
paths and hidden `.htaccess` files. All Phase 1–4 source is supplied;
this delivery is a complete project, not a partial patch.

```text
fayez-os/
├── api/
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── password.php
│   │   ├── profile.php
│   │   ├── README.md
│   │   ├── register.php
│   │   └── session.php
│   ├── calendar/
│   │   └── README.md
│   ├── contacts/
│   │   └── README.md
│   ├── currency/
│   │   ├── rates.php
│   │   └── README.md
│   ├── expenses/
│   │   └── README.md
│   ├── files/
│   │   ├── download.php
│   │   ├── index.php
│   │   └── README.md
│   ├── news/
│   │   ├── feed.php
│   │   └── README.md
│   ├── notes/
│   │   └── README.md
│   ├── settings/
│   │   └── README.md
│   ├── sync/
│   │   ├── README.md
│   │   └── workspace.php
│   ├── tasks/
│   │   └── README.md
│   ├── users/
│   │   └── README.md
│   ├── weather/
│   │   ├── forecast.php
│   │   ├── locations.php
│   │   └── README.md
│   └── health.php
├── app/
│   ├── apps/
│   │   ├── account/
│   │   │   ├── index.js
│   │   │   ├── README.md
│   │   │   └── workspace.js
│   │   ├── calculator/
│   │   │   ├── engine.js
│   │   │   ├── index.js
│   │   │   └── README.md
│   │   ├── calendar/
│   │   │   ├── index.js
│   │   │   └── README.md
│   │   ├── contacts/
│   │   │   ├── avatar.js
│   │   │   ├── index.js
│   │   │   └── README.md
│   │   ├── currency/
│   │   │   ├── index.js
│   │   │   ├── README.md
│   │   │   └── repository.js
│   │   ├── expenses/
│   │   │   ├── index.js
│   │   │   ├── model.js
│   │   │   ├── README.md
│   │   │   └── repository.js
│   │   ├── files/
│   │   │   ├── index.js
│   │   │   ├── README.md
│   │   │   └── repository.js
│   │   ├── music/
│   │   │   └── README.md
│   │   ├── news/
│   │   │   ├── index.js
│   │   │   ├── model.js
│   │   │   └── README.md
│   │   ├── notes/
│   │   │   ├── index.js
│   │   │   └── README.md
│   │   ├── phone/
│   │   │   └── README.md
│   │   ├── qr/
│   │   │   ├── engine.js
│   │   │   ├── index.js
│   │   │   └── README.md
│   │   ├── settings/
│   │   │   └── index.js
│   │   ├── shared/
│   │   │   ├── helpers.js
│   │   │   ├── money.js
│   │   │   └── phase4.js
│   │   ├── tasks/
│   │   │   ├── index.js
│   │   │   └── README.md
│   │   ├── videos/
│   │   │   └── README.md
│   │   └── weather/
│   │       ├── index.js
│   │       ├── model.js
│   │       └── README.md
│   ├── assets/
│   │   ├── css/
│   │   │   ├── apps.css
│   │   │   ├── components.css
│   │   │   ├── phase3.css
│   │   │   ├── phase4.css
│   │   │   ├── phase5.css
│   │   │   ├── shell.css
│   │   │   └── tokens.css
│   │   ├── icons/
│   │   │   ├── icon-192.png
│   │   │   ├── icon-512.png
│   │   │   ├── logo.svg
│   │   │   └── maskable-512.png
│   │   ├── images/
│   │   │   └── README.md
│   │   ├── js/
│   │   │   ├── account-session.js
│   │   │   ├── api.js
│   │   │   ├── boot.js
│   │   │   ├── config.js
│   │   │   ├── icons.js
│   │   │   ├── local-db.js
│   │   │   ├── main.js
│   │   │   ├── registry.js
│   │   │   ├── reminders.js
│   │   │   ├── router.js
│   │   │   ├── scope.js
│   │   │   ├── storage.js
│   │   │   └── ui.js
│   │   └── vendor/
│   │       ├── jsQR-LICENSE.txt
│   │       ├── jsQR.js
│   │       ├── qrcode-LICENSE.txt
│   │       └── qrcode.js
│   ├── .htaccess
│   ├── index.html
│   ├── manifest.json
│   └── service-worker.js
├── config/
│   ├── .htaccess
│   ├── app.php
│   └── database.php
├── database/
│   ├── .htaccess
│   ├── database.sql
│   └── migrate-phase5.php
├── docs/
│   ├── .htaccess
│   ├── CURRENCY_PROVIDER.md
│   ├── DATABASE_PLAN.md
│   ├── FOLDER_STRUCTURE.md
│   ├── PHASE_2.md
│   ├── PHASE_3.md
│   ├── PHASE_4.md
│   ├── PHASE_4_PROVIDERS.md
│   ├── PHASE_5.md
│   ├── PHASE_5_API.md
│   ├── SECURITY.md
│   ├── TEST_REPORT.md
│   ├── TEST_REPORT_PHASE_4.md
│   └── UI_ARCHITECTURE.md
├── includes/
│   ├── .htaccess
│   ├── account.php
│   ├── bootstrap.php
│   ├── currency-provider.php
│   ├── news-provider.php
│   ├── private-files.php
│   ├── public-provider.php
│   ├── security.php
│   ├── weather-provider.php
│   └── workspace.php
├── storage/
│   ├── cache/
│   │   └── .gitkeep
│   ├── private/
│   │   └── .gitkeep
│   └── .htaccess
├── tests/
│   ├── .htaccess
│   ├── api_smoke.py
│   ├── browser_smoke.py
│   ├── phase2_browser.py
│   ├── phase3_browser.py
│   ├── phase4_api_smoke.py
│   ├── phase4_browser.py
│   ├── phase4_provider_test.php
│   ├── phase5_api.py
│   ├── phase5_browser.py
│   ├── phase5_limits.py
│   ├── provider_test.php
│   └── storage_browser.py
├── uploads/
│   ├── .htaccess
│   └── README.md
├── .gitignore
├── .htaccess
├── ANDROID_BUILD.md
├── capacitor.config.json
├── dev-router.php
├── index.php
├── INSTALLATION.md
├── PHASE_2_FA.md
├── PHASE_3_FA.md
├── PHASE_4_FA.md
├── PHASE_5_FA.md
├── README.md
└── THIRD_PARTY_NOTICES.md
```

## Exact file placement and responsibility

All paths below are relative to `htdocs/fayez-os/`.

### Root

| File | Purpose |
|---|---|
| `index.php` | Redirect to the independent static `app/` frontend |
| `.htaccess` | Apache directory protection and base security headers |
| `.gitignore` | Exclude future build artifacts, uploads and local secrets |
| `dev-router.php` | Optional PHP CLI preview with protected private paths |
| `capacitor.config.json` | Native-ready `webDir: app`, package `com.fayez.os` |
| `README.md` | Current scope, running the project, documentation links |
| `PHASE_2_FA.md` | Persian Phase 2 quick-start and local/offline limitations |
| `INSTALLATION.md` | XAMPP, upgrade, optional schema and environments |
| `ANDROID_BUILD.md` | Future Capacitor/Android packaging and signing guide |

### Static frontend

| File | Purpose |
|---|---|
| `app/index.html` | Shared OS shell markup; no business logic or PHP rendering |
| `app/.htaccess` | Static entry, CSP and security headers |
| `app/manifest.json` | PWA name, icons, scope, standalone mode, app shortcuts |
| `app/service-worker.js` | Public shell + implemented app cache; never caches APIs |
| `app/assets/css/tokens.css` | Themes, accent and base controls |
| `app/assets/css/shell.css` | Home, wallpaper, app grid, folders, status and dock |
| `app/assets/css/components.css` | Drawer, app windows, Settings and notices |
| `app/assets/css/apps.css` | Phase 2 app/editor/calendar/calculator UI |
| `app/assets/js/boot.js` | Early appearance-cache read |
| `app/assets/js/config.js` | One API base URL, platform/mode/version |
| `app/assets/js/icons.js` | Local SVG symbols |
| `app/assets/js/registry.js` | App metadata, category and phase availability |
| `app/assets/js/storage.js` | Shared preference repository/cache fallbacks |
| `app/assets/js/local-db.js` | Versioned IndexedDB and transactional app storage |
| `app/assets/js/router.js` | Hash routes, history and internal/native Back bridge |
| `app/assets/js/ui.js` | Escaping, tile components and toast |
| `app/assets/js/api.js` | JSON Fetch, timeouts, CSRF header and errors |
| `app/assets/js/reminders.js` | Foreground-only local calendar reminder scan |
| `app/assets/js/main.js` | Shell orchestration, device signals and app lifecycle |
| `app/apps/shared/helpers.js` | Dialogs, drafts, formatting and shared app UI |
| `app/apps/calculator/engine.js` | Bounded arithmetic/scientific parser |
| `app/apps/calculator/index.js` | Working calculator/history screen |
| `app/apps/notes/index.js` | Working note/checklist library/editor |
| `app/apps/tasks/index.js` | Working task list/dashboard/editor |
| `app/apps/calendar/index.js` | Working month/day calendar/event editor |
| `app/apps/settings/index.js` | Working theme/device/settings module |
| `app/apps/*/README.md` | Module scope; later-app directories explicitly reserved |
| `app/assets/icons/logo.svg` | Vector logo concept |
| `app/assets/icons/icon-192.png` | PWA/touch icon |
| `app/assets/icons/icon-512.png` | Large PWA icon |
| `app/assets/icons/maskable-512.png` | Maskable icon with safe-zone padding |
| `app/assets/images/README.md` | Procedural wallpaper explanation |

Only `app/` will be packaged by Capacitor. PHP/SQL, private server files and native
signing keys must never be copied into it.

### Backend and forward database

| File(s) | Purpose |
|---|---|
| `api/health.php` | Real Phase 4 JSON capability/availability endpoint |
| `api/*/README.md` | Future API namespaces, not fake successful CRUD routes |
| `config/app.php` | Server environment settings, CORS allowlist, DB credentials |
| `config/database.php` | Future PDO connection factory |
| `includes/bootstrap.php` | JSON responses, exception/CORS/method handling |
| `includes/security.php` | Session, auth gate, CSRF and bounded-JSON helpers |
| `database/database.sql` | Unchanged optional 18-table forward MySQL schema |
| `uploads/README.md` | Secure future private-upload policy |
| `config/.htaccess`, `includes/.htaccess`, `database/.htaccess`, `uploads/.htaccess` | Deny direct HTTP access |

All personal apps currently persist locally, not through PHP CRUD. These
backend folders are not a claim that authentication or cloud sync is complete.

### Docs and tests

| File | Purpose |
|---|---|
| `docs/FOLDER_STRUCTURE.md` | This exact tree and every-file map |
| `docs/PHASE_2.md` | App features, limits, data shapes and consistency model |
| `docs/UI_ARCHITECTURE.md` | Shell, routing, module lifecycle, offline/native design |
| `docs/DATABASE_PLAN.md` | Future server tables, constraints, query/index strategy |
| `docs/SECURITY.md` | Existing safeguards and production gates |
| `docs/TEST_REPORT.md` | Executed tests and honest untested boundaries |
| `tests/browser_smoke.py` | Original shell regression, updated availability checks |
| `tests/phase2_browser.py` | Math, CRUD, drafts, reminders, offline and responsive tests |
| `tests/storage_browser.py` | Legacy upgrade and unavailable-storage tests |
| `docs/.htaccess`, `tests/.htaccess` | Keep deployment internals private over HTTP |

Tests require optional Python/Playwright tooling; the application itself does not.
Open documentation from the downloaded files, not public Apache URLs. Do not clear
browser data when upgrading: app records live in that origin's IndexedDB, not in
the project's filesystem. The filesystem stores code, not your browser's notes.

### Phase 3 additions

| File | Purpose |
|---|---|
| `PHASE_3_FA.md` | Current Persian quick-start, upgrade and privacy guide |
| `app/assets/css/phase3.css` | Responsive finance, converter and contact styling |
| `app/apps/shared/money.js` | Exact minor-unit arithmetic and rational rates |
| `app/apps/expenses/index.js` | Transaction/dashboard/budget UI |
| `app/apps/expenses/model.js` | Pure validation and monthly summaries |
| `app/apps/expenses/repository.js` | Unique versioned budgets and safe CSV |
| `app/apps/currency/index.js` | Converter/history/provenance/request lifecycle |
| `app/apps/currency/repository.js` | Validated saved quote and capped history |
| `app/apps/contacts/index.js` | Validated library/detail/editor and drafts |
| `app/apps/contacts/avatar.js` | Safe on-device image resize/re-encode |
| `api/currency/rates.php` | Optional fixed-provider JSON endpoint |
| `api/currency/README.md` | Endpoint scope/setup pointers |
| `includes/currency-provider.php` | Bounded TLS transport and payload normalizer |
| `storage/.htaccess` | Deny HTTP access to server cache directory |
| `storage/cache/.gitkeep` | Empty public-rate cache location; no prices shipped |
| `docs/PHASE_3.md` | New app behavior, exact arithmetic and local data shapes |
| `docs/CURRENCY_PROVIDER.md` | Provider configuration, cache, terms and limits |
| `tests/phase3_browser.py` | Real-module UI/money/concurrency/offline/layout suite |
| `tests/api_smoke.py` | Default-disabled HTTP contracts and protected paths |
| `tests/provider_test.php` | Pure normalization fixtures; optional live request |

`PHASE_2_FA.md` and `docs/PHASE_2.md` remain as historical module documentation.
The source ZIP excludes runtime cache/lock files, private records, credentials,
third-party dependencies, build output and temporary development screenshots.

### Phase 4 additions and updated responsibilities

| File | Purpose |
|---|---|
| `PHASE_4_FA.md` | Current Persian guide, installation/upgrade and privacy limits |
| `THIRD_PARTY_NOTICES.md` | QR library provenance, licenses and wrapper modifications |
| `app/assets/css/phase4.css` | Forecast, reader, QR and local-file responsive screens |
| `app/apps/shared/phase4.js` | Bounded snapshot/history repository, URLs/download helpers |
| `app/apps/weather/index.js`, `model.js` | Place/forecast UI, schema, conditions/cache |
| `app/apps/news/index.js`, `model.js` | Publisher summary reader, bookmarks/history, safe links |
| `app/apps/qr/index.js`, `engine.js` | Generation/scanning/export, opt-in history, stream lifecycle |
| `app/apps/files/index.js`, `repository.js` | Browser-local library, atomic Blob/meta quota/CRUD |
| `app/assets/vendor/qrcode.js` | MIT UTF-8 QR generation algorithm, local module wrapper |
| `app/assets/vendor/jsQR.js` | Apache-2.0 QR decoder, isolated local module wrapper |
| `app/assets/vendor/*-LICENSE.txt` | Distributed dependency license text |
| `api/weather/forecast.php`, `locations.php` | Fixed Open-Meteo JSON routes |
| `api/news/feed.php` | Fixed BBC World/Persian RSS-to-JSON adapter |
| `includes/public-provider.php` | TLS/body/time bounds, bounded protected cache and refresh locks |
| `includes/weather-provider.php`, `news-provider.php` | Pure provider normalization/validation |
| `docs/PHASE_4.md` | New features, state/storage shapes, limits and local-file choice |
| `docs/PHASE_4_PROVIDERS.md` | Extension/env/cache setup, data terms and deployment gates |
| `tests/phase4_browser.py` | CRUD/QR/media-stream/cache/offline/layout/security/quota fixtures |
| `tests/phase4_api_smoke.py` | Query/method/protected-path/CSP HTTP checks |
| `tests/phase4_provider_test.php` | Weather/XML/link fixtures, optional real upstream checks |

`local-db.js` now migrates to v5 / 28 stores (nine added in Phase 8) without dropping
old data. `main.js`
lazy-loads the four modules and displays a dated saved-weather card. `index.html`
remains shell markup, not business logic. `dev-router.php` explicitly serves the
HTML entry with CSP/permission headers; Apache uses `.htaccess`. Only `app/` is
packaged for future native reuse; provider PHP/private server caches stay outside.

### Phase 5 additions

| File | Role |
|---|---|
| `app/assets/js/scope.js` | Frozen guest/account database/cache/channel namespace |
| `app/assets/js/account-session.js` | Session bootstrap, CSRF and private JSON/binary clients |
| `app/apps/account/index.js` | Access/profile/security, workspace and cloud file interface |
| `app/apps/account/workspace.js` | Consistent six-store capture, safe replacement, guest copy |
| `app/assets/css/phase5.css` | Token-based account forms/cards and responsive layouts |
| `includes/account.php` | PDO/auth binding, session epoch, validation and attempt limits |
| `includes/workspace.php` | Bounded snapshot schema and owned SQL read helper |
| `includes/private-files.php` | MIME/signatures/name/size and guarded metadata helpers |
| `api/auth/*.php` | Session/register/login/logout/profile/password routes |
| `api/sync/workspace.php` | Owner-scoped GET/PUT revision-CAS snapshot transfer |
| `api/files/index.php`, `download.php` | Private multipart CRUD and attachment bytes |
| `database/migrate-phase5.php` | CLI-only repeatable existing-schema upgrade |
| `PHASE_5_FA.md`, `docs/PHASE_5*.md` | Current setup/privacy/behavior/API documentation |
| `tests/phase5_*.py` | Real PHP/SQL/browser/ownership/sync/quota regressions |

`storage/private/` runtime content is never shipped. Native PHP session files,
SQL data, limiter buckets, uploads and provider caches are not source assets.


### Phase 6 additions

- `app/assets/css/phase6.css`: search, controls, encrypted backup and privacy curtain.
- `app/assets/js/workspace-runtime.js`: shared page leases / exclusive local restore.
- `app/assets/js/privacy.js`: PIN verifier/cooldown, curtain and app lifecycle.
- `app/assets/js/notifications.js`: transactional center, preferences, system bridge.
- `app/apps/search/index.js`, `model.js`: local index, normalization and safe UI.
- `app/apps/controls/index.js`: notification/PIN/session/storage controls.
- `app/apps/backup/index.js`, `engine.js`, `validate.js`: archive UI/crypto/schema.
- `api/auth/revoke.php`: current-password-confirmed other-session revocation.
- `PHASE_6_FA.md`, `docs/PHASE_6.md`, `docs/BACKUP_OPERATIONS.md`: behavior/recovery.
- `tests/phase6_browser.py`, `phase6_edges.py`, `phase6_api.py`, `phase6_account.py`: optional QA fixtures.
- `docs/TEST_REPORT_PHASE_5.md`: preserved historical results; TEST_REPORT.md is current.

Existing main/boot/storage/local-db/reminders/scope/registry/settings/calendar/account
workspace/service-worker files are integrated rather than duplicated. No generated
DB dump, decrypted archive, real backup, private upload or credential is packaged.


### Phase 7 additions

- `app/apps/media/index.js`, `music/index.js`, `videos/index.js`: shared local player.
- `app/apps/phone/index.js`: contact/keypad UI and validated dialer handoff.
- `app/assets/js/platform.js`, `app/assets/css/phase7.css`: optional bridge/update UI,
  access/keyboard/layout refinements.
- `scripts/prepare-android.mjs`: optional build-time asset staging; never PHP runtime.
- `native/package*.json`, `native/capacitor.config.json`: exact optional toolchain.
- `native/android/`: Gradle wrapper/project, manifest, brand resources, MainActivity
  and FayezNative Java. Generated assets/builds/local.properties/private keys omitted.
- `native/licenses/`, `native/THIRD_PARTY_NOTICES.md`: native dependency notices.
- `native/.htaccess`, `scripts/.htaccess`: deny web access to build internals.
- `PHASE_7_FA.md`, `docs/PHASE_7.md`, current ANDROID_BUILD.md.
- `tests/phase7_browser.py`, `phase7_native_bridge.py`: new explicit QA scopes.
- `tests/phase7_media_size.py`: large-media playback fix — oversized saved picks,  74 MB/720p and 16 MB WAV files played from device, guards, regressions, overflow.
- `docs/TEST_REPORT_PHASE_6.md`: historical Phase 6 evidence.

### Phase 8 additions — fourteen applications

| File | Role |
|---|---|
| `app/apps/clock/index.js` | Stopwatch with laps, countdown timer, presets, WebAudio chime |
| `app/apps/focus/index.js` | Pomodoro cycles, own lengths, session log |
| `app/apps/habits/index.js` | Daily habit check-in, streaks, seven-day view |
| `app/apps/shopping/index.js` | Shopping list with quantity, category, clear completed |
| `app/apps/converter/index.js` | Unit conversion with exact local factor tables |
| `app/apps/passwords/index.js` | Random secrets/passphrases, entropy, nothing stored |
| `app/apps/dates/index.js` | Jalali ↔ Gregorian conversion, month grid, day difference |
| `app/apps/health/index.js` | Exact age and BMI, informational only |
| `app/apps/docs/index.js` | Markdown editor with safe preview, autosave, export |
| `app/apps/reading/index.js` | Bookshelf with pages, progress, ratings and stats |
| `app/apps/words/index.js` | Live word/character/sentence counts and top words |
| `app/apps/voice/index.js` | Local recording, playback and export (`voice` + `voiceBlobs`) |
| `app/apps/photos/index.js` | Rotate/flip/crop/resize, download or save into Files |
| `app/apps/sketch/index.js` | Freehand canvas with undo, save/export PNG (`sketches` + `sketchBlobs`) |
| `app/apps/shared/local-store.js` | Shared repository for the nine new stores |
| `app/apps/shared/markdown.js` | Safe Markdown renderer and text counter |
| `app/assets/css/phase8.css` | Shared building blocks for the new apps |
| `PHASE_8_FA.md`, `docs/PHASE_8.md` | Persian and technical notes for this pack |
| `tests/phase8_apps.py` | One behaviour check per app, end to end |
| `tests/phase8_integration.py` | Persistence, search coverage, backup round-trip, layout |
| `tests/phase8_smoke.py` | Quick mount/error smoke for all fourteen apps |
| `tests/phase8_accessibility.py` | Axe-core spot check (28 audits) and markup-escaping assertion |


The debug APK is a separate artifact, not a secret-containing source ZIP member.
Ordinary web delivery remains app/api/config/database/uploads-separated.
