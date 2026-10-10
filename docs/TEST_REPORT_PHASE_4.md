# Test report — Fayez OS 0.4.0

Executed **28 September 2026**, development workspace: PHP 8.4.26, cURL/DOM/XML,
Chromium 153 and Python Playwright. Isolated browser contexts and synthetic data;
no user records, real news/forecast snapshots or private files are seeded in the
release. Test tools are not runtime dependencies.

## Phase 4 browser suite — passed

`tests/phase4_browser.py` uses the actual local modules and QR algorithms. Network
fixtures are explicitly synthetic, not market/news/weather claims.

- Weather starts without an automatic request. Explicit refresh renders current,
  24-hour and 7-day data; C/F conversion, stored selection/unit/reload, source/time/
  stale label, city search and selection, denied geolocation and saved home card.
- News: 12-item pagination, search, safe injection-shaped title/summary, explicit
  publisher links, summary detail/internal Back, bookmark, reading-history clear,
  World/Persian selection and persistence. No full article is copied.
- QR: Persian/Unicode encoder-to-real-decoder round trip; downloaded PNG signature
  and re-decoding; generated SVG without executable content; Wi-Fi escaping;
  unsupported link scheme rejection; input changes invalidate old downloads.
- Scanning: actual PNG decode, SVG rejection, denied camera, synthetic MediaStream
  decoding, tracks stopped on success, and delayed permission resolving after
  navigation immediately stops the abandoned stream. No physical camera claimed.
- QR history is opt-in; saved entries and confirmed clear work. A Wi-Fi password
  is not automatically written to history.
- Files: multi-import, metadata and Blob persistence, escaped UTF-8 preview, exact
  byte-for-byte download, unsupported-type rejection, same-extension rename,
  search/category filters, bounded image preview, confirmed deletion of metadata
  **and content**, reload and actual two-tab stale-rename conflict.
- Offline controlled-worker reload: dated saved weather/news/bookmarks, QR
  generation/history and a real local file import.
- **14 additional checks** cover filenames, invalid signatures/extensions,
  empty/oversized files, QR byte limits, URL safety and aggregate quota. Five
  10 MiB files fill the 50 MiB library; another file is rejected without an
  orphaned metadata/content record.
- All four populated apps, QR canvas, news reader, file previews and rename dialog
  at **320, 360, 375, 390, 412, 430, 768, 1024 and 1440 px**, both dark and light.
  No document/app/dialog horizontal overflow. Selected screenshots visually reviewed.
- **Zero uncaught browser errors** in the final run.

The Phase 4 suite also passed with an active restrictive CSP, **without disabling
CSP** in Playwright. The harness was corrected to wait for an explicit readiness
attribute rather than using Playwright's eval-based wait predicate. The app itself
never requires `unsafe-eval`. The CLI router was fixed to actually send HTML CSP
headers rather than lose them through PHP's static-file fallback.

A separate cold-offline check loaded only the shell, then opened QR and Files for
the first time offline: QR generation/decoding and file import passed. This checks
precache coverage, not merely previously visited app modules.

## Migration and blocked storage — passed

`tests/storage_browser.py` checks:

- v1 appearance → v4, full **19-store** set.
- Populated v2 → v4: note/task/event/calculation/draft data and record versions
  preserved; retained note and draft also verified through the UI.
- Populated v3 → v4: exact transaction amount/version, budget, Persian contact/
  favorite, conversion data, stored quote and contact draft preserved; new Files
  store starts empty. No destructive reset is used.
- Blocked IndexedDB: Notes/Expenses/Currency/Contacts/Files/QR/News show honest
  data-storage errors, not a claimed ephemeral save. Calculator still computes
  while reporting unavailable saved history.

## Providers, HTTP and security — passed

`tests/phase4_provider_test.php`: **25 deterministic checks** for weather schema,
numeric fields, missing arrays/timezone, RSS text/date/link normalization, unsafe
URL hosts/schemes/credentials/ports, XML DTD/entity rejection and malformed/empty
RSS. Does not execute upstream HTML or read external entities.

Optional `--live` also actually fetched and validated:

- Open-Meteo: current conditions, **24 hours / 7 days**.
- BBC World: **25** usable feed entries at test time.
- BBC Persian: **28** usable feed entries at test time.

Additional real HTTP/browser checks rendered an actual forecast, opened/saved an
actual publisher summary, and round-tripped a locally generated QR. Those real
provider values were not put in the source distribution. Counts/content may
change later; availability is not guaranteed.

`tests/phase4_api_smoke.py`: **14 HTTP cases** for missing/out-of-range/array
coordinates, unexpected query/URL parameters, invalid city/feed values, wrong
methods and protected paths; plus real CSP/Permissions-Policy header checks.
`tests/api_smoke.py` reruns the Phase 3 default-disabled currency contract, health,
method/query handling, no-store/nosniff and protected directories.

Provider transport verifies TLS, bounds bodies/timeouts and disallows redirects.
Actual tests are not an exhaustive malformed-provider, cache-permission,
concurrent-multi-worker, load/DDoS or penetration-test certification.

## Previous regression suites — passed again

- `tests/browser_smoke.py`: shell, 14 home/16 drawer entries, now eleven functional
  personal apps, remaining future previews, search/categories, Back, theme/settings
  persistence, notifications, keyboard, system theme, API and offline Settings.
- `tests/phase2_browser.py`: 26 arithmetic/scientific values, 14 rejected inputs;
  calculator history, notes/checklists/format safety, tasks, calendar, foreground
  notices, drafts/conflicts, offline writes and responsive controls.
- `tests/phase3_browser.py`: 55 pure money/model assertions; exact ledgers/budgets/
  CSV; currency reciprocal/history/cache provenance/input-save race; contacts/local
  image processing; drafts, two-tab conflicts, offline writes and both themes.
- Shell/new apps: nine widths 320–1440 px; Phase 2: its original eight-width matrix.
  Previous suites finish without uncaught browser errors.

**17 PHP files** passed lint; **38 JavaScript files** passed syntax/import checks.
Manifest/config JSON, local HTML/documentation links and **50 unique precache
assets** were checked. Final coherent worker cache is **v4.0.2**. Previous app and
shell suites also passed again with the restrictive CSP active.
The optional 18-table forward MySQL schema is unchanged, not Phase 4 runtime
storage; the earlier MariaDB schema validation is not claimed as a new MySQL run.

## Reproduce

```sh
python -m pip install playwright
python -m playwright install chromium
# Linux may need: python -m playwright install-deps chromium
php -S 0.0.0.0:8080 -t . dev-router.php
# In another terminal, with default currency disabled and weather/news enabled:
python tests/browser_smoke.py
python tests/phase2_browser.py
python tests/phase3_browser.py
python tests/phase4_browser.py
python tests/storage_browser.py
python tests/api_smoke.py
python tests/phase4_api_smoke.py
php tests/provider_test.php
php tests/phase4_provider_test.php
# Optional real upstream network calls:
php tests/phase4_provider_test.php --live
```

`FAYEZ_URL` changes the server; `FAYEZ_SHOTS` optionally saves Phase 3/4 or shell
screenshots. All test data is isolated from the user's actual browser profile.

## Not claimed / release gates

No physical Android/iOS/camera, Safari/Firefox, native permission/intent/hardware
Back or signed APK/AAB validation; no full keyboard/font-scaling/screen-reader
audit. Browser resizing and synthetic camera streams are not real devices.

No direct XAMPP desktop/Apache or PHP 8.0/MySQL 8 integration run. Supplied Apache
rules and extensions must be checked in the actual hosting configuration.

No authentication, encrypted/account-partitioned local storage, private server
upload, cloud sync, full backup/restore, malware scanner, ZIP extraction or inline
PDF/Office execution. Codec support depends on the browser.

No independent verification of news, guaranteed weather accuracy, bank-rate
quote, commercial provider license, uptime/SLA, distributed throttling or large
production benchmark. Review current data-use terms before public deployment.

Browser quota exhaustion beyond the app cap, corrupt-profile recovery, long
suspension, simultaneous draft merging, unusual camera/image inputs and every
production egress/certificate/cache failure need more testing.
