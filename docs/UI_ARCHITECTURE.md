# UI & application architecture — version 0.6.0

## Preserve the original design

The Phase 1 Aurora shell is unchanged in visual direction: deep blue-green, muted
app colors, mint accents, glass panels, procedural ridges, restrained motion and
native font stacks. Phases 1–5 use no external fonts, runtime CDNs or UI frameworks. Two local QR
algorithm libraries are isolated in `assets/vendor/`; see THIRD_PARTY_NOTICES.

- `tokens.css`: dark/light/system/accent semantic colors and shared controls.
- `shell.css`: status bar, wallpaper, home grid, folders, dock and breakpoints.
- `components.css`: shared app windows, drawer, notifications and Settings.
- `apps.css`: Phase 2 calculators, lists, cards, editors and calendar; inherits tokens.
- `phase3.css`: money charts, converter and contact list/detail/editor, sharing tokens.
- `phase4.css`: forecast, reader, QR and local-file screens, inheriting shared tokens.
- `phase5.css`: account access, profile/security, manual transfer and cloud file cards.
- Full-height phone app windows; desktop app windows are bounded and internally
  scrollable. `env(safe-area-inset-*)`, `100dvh`, reduced-motion and focus states
  carry through new modules. No hover-only actions.

## One shell

```text
app/index.html
  .os-shell: status / home / app grid / folders / dock
  #drawer: app search / category filters / recents
  #app-window: shared Back, title and Close / #app-content mount region
  #notifications: real local system + foreground calendar notices
  #toast: nonblocking feedback
```

No app duplicates browser navigation or routes to a PHP-rendered page. App data
logic lives in modules, not HTML. Future applications plug into this shell.

## Routing and lifecycle

| Route | Current behavior |
|---|---|
| `#home` | Home |
| `#drawer`, `#drawer/Productivity`, etc. | App drawer / category |
| `#search` | App search with focused input |
| `#app/calculator` | Functional calculator |
| `#app/notes` | Functional local notes library/editor |
| `#app/tasks` | Functional local tasks |
| `#app/calendar` | Functional local month/day calendar |
| `#app/expenses` | Functional monthly dashboard, budgets and transaction editor |
| `#app/currency` | Manual/provider converter with provenance and local history |
| `#app/contacts` | Functional local library/detail/editor |
| `#app/weather` | Explicit forecasts/search, optional location, dated offline cache |
| `#app/news` | Publisher RSS summaries, saved/history and explicit source links |
| `#app/qr` | Local generation, download and camera/image decoding |
| `#app/files` | Browser-local metadata/blob library, preview/download/rename/delete |
| `#app/account` | Identity, explicit workspace transfer, private server files |
| `#app/settings` | Functional appearance/device settings |
| Other registered `#app/*` | Explicit future-phase previews |
| `#notifications` | Local OS notice center |

The hash router works without rewrite rules and remains compatible with static
Capacitor assets. Browser Back consumes history. In-app Back may first close a
local editor/history/detail pane; browser Back leaves the app after pending draft
writes are flushed. Internal editor state does not require a page reload.

An implemented app ES module exports:

```js
export async function mount(container) {
  // Load local records and render inside this mount node.
  return {
    beforeLeave: async () => { /* flush pending draft writes */ },
    destroy: () => { /* abort event listeners, close app dialogs */ },
    back: () => false, // true if an internal view handled Back
    focus: () => { /* optional initial keyboard focus */ }
  };
}
```

`main.js` uses explicit, allowlisted import functions, not an arbitrary path from
user input. Async openings have route tokens and isolated mount nodes; stale
loads cannot overwrite the current app. A failed load/storage open shows an error
and Retry. Module listeners are aborted when the module is released. Shell routes
remain available; no full-page navigation is introduced.

`FayezOS.back()/home()/navigate()` remain the future native bridge. Capacitor's
hardware-back adapter can call the same functions; no Android-only code is needed
for normal XAMPP use.

## Accessible interactions

Background shell becomes inert when an app/drawer is open. Overlay focus is
trapped and restored. `/` invokes app search unless editing text; Escape uses Back.
Native HTML `<dialog>` is used for task/event forms and destructive confirmations;
the shell defers its Escape/Tab handling to open modal dialogs. All data fields
have labels. Completion controls expose checked states and calendar dates expose
full date labels. Formatting previews are rendered as text/allowed elements.

App gestures remain optional: the original upward dock swipe opens the drawer;
all gestures have visible tap/click controls. The UI accepts Persian/Unicode note,
task and event text using automatic text direction. It is not yet an app-wide RTL
localization or non-Gregorian calendar.

## Module boundaries

| Module | Owns |
|---|---|
| `boot.js` | Early validated appearance cache to prevent theme flash |
| `config.js` | One API URL, mode, platform, version and timeout |
| `icons.js` | Local SVG library |
| `registry.js` | App metadata/category/phase availability |
| `storage.js` | Shell preference cache/fallbacks using the shared DB |
| `local-db.js` | IndexedDB v4, record transactions/versioning, history/drafts |
| `router.js` | Routes, history, internal Back interceptor, native bridge |
| `ui.js` | Safe escaping, app icons/tiles, toasts |
| `api.js` | JSON Fetch, credentials/CSRF headers, timeout and errors |
| `reminders.js` | Foreground local calendar reminder scheduler |
| `main.js` | Shell orchestration, module lifecycle and notification publication |
| `apps/shared/helpers.js` | App UI helpers, safe formatting, dialogs/draft sessions |
| `apps/*/index.js` | App-specific UI and workflows |

## Local persistence, not pretend cloud functionality

One IndexedDB database, `fayez-os` version 5 (28 stores; Phase 8 added nine). Upgrades add stores/indexes without
dropping old preferences. Core stores:

- Preferences: appearance, notices/recents, calculator state, reminder fingerprints.
- Notes/tasks/events: client UUIDs, creation/update times, version, deletion marker.
- Calculations: capped 100-entry history.
- Drafts: recoverable editor state; synchronous small localStorage cache +
  debounced serialized IndexedDB writes. One draft per app/origin.
- Transactions/budgets/contacts: versioned local financial and contact records.
- Conversions: capped 100-entry exact-ratio/provenance history.
- ExchangeRates: latest validated provider quote for explicit dated offline use.
- WeatherCache/newsCache: bounded snapshots, separate from service-worker asset cache.
- SavedArticles/newsHistory/qrHistory: bounded local entries; QR is opt-in.
- Files/fileBlobs: atomic metadata/content stores and quota checks.
- Outbox: reserved and empty until account/sync ownership is implemented.

Writes use optimistic version checks within a transaction. Existing-record
conflicts preserve the draft and offer an explicit separate-copy action. Record
save + draft tombstone is atomic. Personal-data apps never claim an in-memory
fallback is a durable save. On storage failure, they report an error rather than
silently discarding or uploading anything. See `PHASE_2.md`, `PHASE_3.md` and `PHASE_4.md` for field limits and data shapes.

No local data is encrypted or user-partitioned yet. A future account feature must
obtain consent to import unowned records and isolate users on login/logout.

## PHP boundary

The frontend remains static. `/api/health.php` reports capabilities, not database
health or authentication. `/api/currency/rates.php` is an optional fixed-provider
JSON adapter, disabled by default. It receives no private amounts or records.
All network API calls use `api.js` and the single configured base URL. PHP never renders personal-app HTML.
PDO/session/CSRF helpers and an optional 18-table SQL plan remain foundations for
later phases. No dummy CRUD/auth route returns false success.

Future JSON responses use `{success, message, data}` and appropriate HTTP status.
Server authorization must derive ownership from the authenticated session. Do not
trust a client user ID or simply insert arbitrary JavaScript objects into SQL.

## Offline PWA

The worker precaches the whole public shell and all implemented app modules, so
first opening a new app offline after installation works. It excludes APIs,
authorization responses and private user records. Versioned shell assets use
cache-first for a coherent release; the API config is network-first with cache
fallback. Updated workers wait for old app windows to close.

The shell, Settings and implemented local tools function offline after a
successful HTTPS/localhost load. Currency uses manual or explicitly dated saved
rates offline; a new provider quote needs the network. Future server apps do too.
The root PHP redirect is not cached; installed apps start inside `/app/`.
Foreground reminder timers are not a service-worker or OS alarm system.

## Historical synchronization plan (implemented as manual Phase 5 snapshots)

1. Assign/import local records to an authenticated user with explicit consent.
2. Persist entity mutations + operation UUIDs in an IndexedDB outbox atomically.
3. Authenticate and obtain CSRF token; submit bounded batches to PHP over HTTPS.
4. Scope by session user, deduplicate operation IDs, compare base versions and
   commit transactionally. Return 409 for conflicts, never silently overwrite.
5. Pull by server cursors, preserve deletion tombstones, acknowledge only after
   commit. Stop on 401, retry transient failures with backoff.
6. Partition local stores and revoke session data on account change/logout.

The future MySQL plan already anticipates notes/tasks client UUIDs and versions.
Events need an additional migration before sync. None of this upload machinery is
active now; an empty outbox does not mean records are cloud-backed up.

## Native reuse

Capacitor packages `app/` as-is. PHP/MySQL stay on a configured HTTPS server.
Native hardware Back, camera, notification scheduling, secure session storage and
adaptive icons belong in narrow platform adapters, not a redesign of these apps.
`ANDROID_BUILD.md` provides the planned packaging steps and untested release gates.

## Icon system and app tiles

All glyphs live in `app/assets/js/icons.js` as raw SVG markup inside one
`viewBox="0 0 24 24"`. `svg.icon` in `tokens.css` supplies the size (22px),
`fill: none`, `stroke: currentColor`, `stroke-width: 1.65` and round caps/joins,
so a glyph never has to restate them.

Rules when adding one:

1. Draw on the 24×24 grid and keep the artwork inside it, remembering that half
   of the 1.65 stroke sits outside the path (roughly 0.8 units).
2. Centre it. `tests/phase8_icons.py` fails if a glyph drifts more than 1.8
   units horizontally or 2.0 vertically from the middle, or fills less than 12
   of the 24 units.
3. Give the app a tile colour (`.icon-<id>` with a `linear-gradient`) and keep at
   least 3:1 contrast against the white glyph. `.app-icon` in `shell.css` carries
   a fallback gradient so a tile is never left empty.
4. Reuse an existing glyph only when it means the same thing — the launcher check
   fails when two apps end up sharing artwork.

## Phase 3 specific boundaries


Exact BigInt money/rational conversion is separate from the floating-point
scientific calculator. Each currency has a separate ledger. Pure expense models
and repositories are separate from UI code. Contact raster photos are validated
and re-encoded locally; text is escaped and only safe JPEG data URLs are rendered.
The provider adapter uses a fixed HTTPS URL and protected public-rate-only disk
cache. See `PHASE_3.md` and `CURRENCY_PROVIDER.md`. Manifest shortcuts intentionally
retain the four Phase 2 launchers; every new app is available in the shell/drawer.

## Phase 4 network and local tools

Weather/news PHP adapters use fixed hosts, TLS verification, bounded bodies,
explicit queries and a protected bounded public-data cache. No file/QR content
is uploaded. The home weather card reflects only a saved forecast and labels its
age, not an unsolicited live location fetch. Provider terms/production gates are
in `PHASE_4_PROVIDERS.md`.

QR camera tracks stop on success, Stop, hide, mode change and destroy, including
late permission promises. Decoder imports are local modules, not injected CDN
scripts. File previews use short-lived Blob URLs; PDF/Office/ZIP never execute
inside the app. Native file/permission/intent adapters still need device tests.

The shell exposes `html[data-fayez-ready="true"]` after boot for CSP-safe test
readiness. No application code needs unsafe-eval. Development HTML uses the same
CSP/permission policy as the supplied Apache entry configuration.

## Phase 5 identity boundary

`scope.js` freezes the guest/account namespace before any database open. Startup
checks the server identity, changes scope only through a full reload, and labels
Home with the selected workspace. Drafts, preference caches and data channels are
namespaced too. The original guest database is preserved. `account-session.js`
keeps CSRF in memory; account IDs in localStorage are not credentials.

Account is a lazy module. API status, actions and confirmations distinguish local
copies from cloud snapshots. UI lifetime cancellation prevents late DOM work;
interrupted uploads explicitly advise refresh because a server commit may have
completed. Other tabs reload on identity changes and workspace replacements.

The CLI preview allows framing for Arena. The Apache production CSP additionally
sets frame-ancestors self; embedded preview users should open the app in its own
tab for reliable SameSite cookies. No native cookie transport is claimed yet.


## Phase 6 runtime and UI

Main acquires a scoped lifetime shared Web Lock before reading local state, runs the
startup privacy gate, then initializes routes. Synchronous boot marks a configured
privacy curtain before data-app render. Locking destroys mounted app controllers
(including camera streams), preserves draft flushes and suppresses private route
rendering/toasts. Unlock returns a warm session home; cold startup then renders the
requested route. Controls and Backup are lazy app modules; all new assets are in
the public offline shell allowlist. Settings links remain ordinary route buttons.

Home retains 15 launchers; the drawer has 20 (including Find, Controls, Backup and
the Search alias). App-only drawer filtering remains; the home search/keyboard slash
shortcut now searches saved local records through Find. New responsive styles are
isolated in phase6.css. See PHASE_6.md for data/crypto and concurrency contracts.
