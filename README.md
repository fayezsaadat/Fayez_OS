# Fayez OS

**Version 0.7.0 · Phases 1–7 implementation · Android debug preview**

A mobile-first mini OS: static HTML/CSS/vanilla JavaScript, modular PHP JSON API,
MySQL-compatible accounts, local-first tools and a public-assets-only PWA cache.
No React, Vue, Laravel, Node server or web build step. Local QR algorithms are
bundled with licenses; nothing depends on a runtime CDN.

**[راهنمای فارسی فاز هشتم](PHASE_8_FA.md)** · [Phase 7 notes (فارسی)](PHASE_7_FA.md) ·
[Installation](INSTALLATION.md) · [Actual test report](docs/TEST_REPORT.md)

## What's new in Phase 8 — fourteen new applications

- **Everyday order:** Clock (stopwatch/laps + countdown), Focus (Pomodoro with your
  own lengths and a session log), Habits (daily ticks, streaks, seven-day view),
  Shopping (quantities, categories, clear completed).
- **Everyday tools:** Convert (length, mass, temperature, volume, area, time, speed,
  data — exact local factors), Passwords (device-random secrets and passphrases,
  entropy estimate, nothing stored), Dates (Jalali ↔ Gregorian, month view, day
  difference), Health (exact age, BMI and a healthy range).
- **Writing and reading:** Writings (Markdown editor with a safe preview, autosave and
  `.md` export), Reading (books, pages, progress, ratings, notes, stats), Word count
  (live counts, reading time, top words, Persian/Arabic normalisation).
- **Media and images:** Voice notes (local recording, playback and export), Photo edit
  (rotate, flip, crop, resize; the original file is never touched or uploaded), Sketch
  (freehand canvas with undo and PNG save/export).

All fourteen are local-first: IndexedDB v5 (28 stores), included in the encrypted
backup, indexed by local search, and **no server sync** — see
[Phase 8 notes](docs/PHASE_8.md) for the data model and honest limits.

## What's new in Phase 7

- **Music / Videos:** local playback of MP3/WAV/MP4/M4V/MOV/WebM/MKV/OGG/FLAC;  10 MiB cap only for files saved into the Files library — “Play from device” plays  any size without storing it. Shared Files library,
  import/search, rate/loop/next controls and lifecycle cleanup. No background service.
- **Phone:** keypad, saved-contact selection and explicit device dialer handoff.
  No automatic call, SIM/call-log access or service-code execution.
- **Performance/accessibility:** bounded search reads/cached ranking keys, staged
  result DOM, skip link, visible focus, touch targets and reduced-motion support.
- **PWA updates:** explicit status/check with safe close-all-windows activation.
- **Android:** optional Capacitor 8.5.2 / Android Studio source and a real debug-signed
  APK. Same frontend, native Back/SAF export/dialer/lifecycle adapters and Fayez icons.
  The delivered APK is intentionally **local-only**, not a localhost-connected demo.

No physical Android/SAF/dialer/TalkBack test, signed release AAB or store approval is
claimed. The web build retains PHP/MySQL accounts; native cloud/provider services
are clearly disabled pending a reviewed production HTTPS/auth setup. See
[Android build/install gates](ANDROID_BUILD.md) and [Phase 7 details](docs/PHASE_7.md).

## Preserved Phase 6 tools

- **Find:** real current-workspace search with Persian/Arabic normalization, type
  filters and safe previews. No query upload/history, QR secrets or file-content scan.
- **Controls:** foreground calendar/task notification choices, quiet hours, explicit
  browser permission/test and generic system text by default. No closed-app push.
- **Privacy PIN:** startup/idle/hidden-page screen shield, cooldown and verified
  change/disable. **Not encryption of the live local database or server sign-out.**
- **Backup:** passphrase-encrypted `.fayezbak` across all 19 supported local stores,
  including file bytes/newer drafts. Validated preview, same-workspace restriction,
  exclusive-tab lease, atomic replacement and crash-safe stale-cache barrier.
- **Session security:** password-authenticated sign-out of other server sessions;
  optional browser persistent-storage request (not a backup).

Read [Phase 6 contract/limits](docs/PHASE_6.md) and
[local vs full-server backup operations](docs/BACKUP_OPERATIONS.md).

## Preserved Phase 5 account features

- **Account:** real registration/login/logout, profile and password change,
  password hashing, secure-cookie configuration, CSRF, expiry and session revocation.
- **Separate workspaces:** the original guest data is kept; each account has its own
  local DB, preferences, draft cache and library. Copying guest records is explicit.
- **Manual cloud workspace:** transfer notes/tasks/events/transactions/budgets/
  contacts (including small photos) between devices through MySQL, with confirmation,
  server revision checks and local-race checks. **Not automatic sync or record merge.**
- **Private cloud files:** explicit upload, authenticated download, search, rename
  and delete. Ownership checks, type/signature validation, random storage keys,
  10 MiB/file and 50 MiB/100-file account quota. Local Files stays separate.

No email verification/recovery, encryption of the live local database, server-wide
automatic backup/version history or production-signed Android release is claimed. Logout retains separate local
account copies; a shared browser profile is not a safe place for sensitive data.
See [Phase 5 behavior and limits](docs/PHASE_5.md).

## Preserved working tools

| Tool | Main functions |
|---|---|
| Calculator | Basic/scientific parser, history, offline calculations |
| Notes | Text/checklists, categories, pin, local formatting and drafts |
| Tasks | Priorities, due dates, completion, search and drafts |
| Calendar | Monthly/day views, events and foreground reminders |
| Expenses | Exact income/expense ledgers, separate currencies, budgets, CSV |
| Currency | Manual rates, optional dated reference provider, history/cache |
| Contacts | Search, favorites, call/mail links, local photo resizing |
| Weather | Explicit Open-Meteo model forecasts, city search, C/F, dated cache |
| News | BBC World/Persian summaries/links, search, bookmarks/history |
| QR Code | Text/link/Wi-Fi generation, PNG/SVG, local image/camera decoding |
| Files | Browser-local import/preview/download/rename/delete, no silent upload |
| Clock / Focus / Habits / Shopping | Foreground stopwatch, timer and Pomodoro; daily habits; shopping lists |
| Convert / Passwords / Dates / Health | Unit conversion, secret generation, Jalali↔Gregorian dates, age and BMI |
| Writings / Reading / Word count | Markdown editor, bookshelf with progress, live text statistics |
| Voice notes / Photo edit / Sketch | Local recording, non-destructive image edits, freehand canvas |
| Settings | Light/dark/system, wallpapers, accents, diagnostics/install guidance |

Weather/news fetch only on explicit actions; no fabricated live values. Currency's
optional provider is disabled by default. Public/commercial data use requires
current provider-rights review, especially normalized BBC summaries. See
[provider terms/setup](docs/PHASE_4_PROVIDERS.md) and
[currency setup](docs/CURRENCY_PROVIDER.md).

## Quick start

1. Put the complete `fayez-os` folder in XAMPP `htdocs`; retain `.htaccess` files.
2. Start Apache and open `http://localhost/fayez-os/`. Guest tools need no MySQL.
3. For accounts, start MySQL/MariaDB, import `database/database.sql`, configure a
   least-privilege DB user and PHP `pdo_mysql`/`fileinfo`; see **INSTALLATION.md**.
4. For an existing SQL installation, back it up and run the CLI-only
   `database/migrate-phase5.php` with migration privileges. Never drop user data.
5. Set localhost `APP_ENV=development`; public hosting must use HTTPS. A trusted
   TLS proxy needs explicit `APP_HTTPS=1` for Secure cookies.
6. Configure private file/session/limiter storage and PHP upload limits. Open
   **Account**, or Settings → Manage account. No default user/password is seeded.

A PHP CLI development server is also supported:

```sh
# Supply your own DB environment variables for accounts; never commit secrets.
APP_ENV=development php -d upload_max_filesize=10M -d post_max_size=12M \
  -S 0.0.0.0:8080 -t . dev-router.php
```

Not production hosting. Embedded previews may block SameSite session cookies;
open the live URL in its own browser tab to sign in. Apache's supplied CSP forbids
cross-origin framing; the CLI preview router intentionally permits embedding.

## Upgrade / data safety

Replace code at the same origin/path, preserve server configuration/SQL/private
files, close old PWA/browser windows and reopen online. Worker **v5.0.1** precaches
all modules. Guest IDB is now **v5 / 28 stores** (nine stores added for the Phase 8 apps);
old v1/v2/v3/v4 records migrate safely and nothing is cleared on upgrade.
Account IDs select separate v4 databases. **Do not clear site data to update.**

Workspace transfer excludes drafts/preferences/history/QR passwords/provider
caches/local files. It keeps one cloud copy, not a backup archive. Source-folder
backups do not back up browser records, MySQL or private uploaded bytes.

## Documentation

- [PHASE_7_FA.md](PHASE_7_FA.md): Persian current web/Android delivery guide.
- [docs/PHASE_7.md](docs/PHASE_7.md): actual native, media and optimization boundaries.
- [PHASE_6_FA.md](PHASE_6_FA.md): Persian search, notifications, PIN and backup guide.
- [docs/PHASE_6.md](docs/PHASE_6.md): archive protocol, limits and consistency.
- [docs/BACKUP_OPERATIONS.md](docs/BACKUP_OPERATIONS.md): local/server recovery procedures.
- [PHASE_5_FA.md](PHASE_5_FA.md): Persian setup, account/privacy/transfer guide.
- [docs/PHASE_5.md](docs/PHASE_5.md): scope, architecture, consistency and limits.
- [docs/PHASE_5_API.md](docs/PHASE_5_API.md): REST routes, cookies, limits and errors.
- [INSTALLATION.md](INSTALLATION.md): XAMPP, SQL migration, permissions and HTTPS.
- [ANDROID_BUILD.md](ANDROID_BUILD.md): later Capacitor/Android packaging gates.
- [docs/SECURITY.md](docs/SECURITY.md): implemented boundaries and deployment gates.
- [docs/FOLDER_STRUCTURE.md](docs/FOLDER_STRUCTURE.md): complete packaged tree.
- [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md): QR provenance/licenses.

## Tests

Optional Python Playwright + requests; not web runtime dependencies. See
`docs/TEST_REPORT.md` before running against a **disposable**, configured SQL server.
The Phase 5 tests create synthetic accounts and exercise real uploads and quotas.
Never use a user's browser profile or production database for these fixtures.

## Remaining release gates — not silently claimed

Physical-device/Android permission, SAF export, keyboard, lifecycle, codecs and
TalkBack validation; reviewed native production backend/session architecture; your
own release signing/Play setup; provider rights; independent security and deployment
review. Music/Videos/Phone are now working local tools, not future-preview cards.
The browser app still needs no Node/build step. Only Android development uses the
isolated native/ toolchain; no SDK, node_modules or private key is shipped in source.
