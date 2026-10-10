# Phase 8 — the new application pack (0.8.0)

Fourteen applications were added with the same rules as the rest of Fayez OS: vanilla
ES modules, no framework, no build step for the web, no network calls for data, and no
silent server sync. Each app is one folder under `app/apps/<id>/index.js` exporting
`async function mount(container)` and returning `{destroy, back?, beforeLeave?}`, the
same contract the shell has used since Phase 1.

## The apps

| id | Name | Storage | Notes |
| --- | --- | --- | --- |
| `clock` | Clock | preferences (`clock-presets`) | Stopwatch with laps, countdown timer with presets, WebAudio chime. Foreground only. |
| `focus` | Focus | `focus` + preferences (`focus-settings`) | Pomodoro cycles, own lengths, session log that marks skipped sessions. |
| `habits` | Habits | `habits` | Daily check-in, streak/best streak, seven-day view, per-habit colour. |
| `shopping` | Shopping | `shopping` | Quantity, category, tick off, clear completed. |
| `converter` | Convert | none | 8 categories with exact factor tables, copy result. |
| `passwords` | Passwords | none | `crypto.getRandomValues` with rejection sampling, passphrase mode, entropy estimate. |
| `dates` | Dates | none | Jalali ↔ Gregorian via Intl, month grid, day difference, honest fallback. |
| `health` | Health | none | Exact age, days lived, next birthday, BMI with a healthy range. |
| `docs` | Writings | `docs` | Markdown editor, safe preview, autosave, export `.md`. |
| `reading` | Reading | `reading` | Books with pages, progress, status, rating, notes and stats. |
| `words` | Word count | none | Live counts, reading time, top words, Persian/Arabic normalisation. |
| `voice` | Voice notes | `voice` + `voiceBlobs` | MediaRecorder capture, playback, export, delete. Needs microphone permission. |
| `photos` | Photo edit | none (outputs to Files or a download) | Rotate, flip, drag-to-crop, resize, PNG/JPEG/WebP output. |
| `sketch` | Sketch | `sketches` + `sketchBlobs` | Freehand canvas, colours, thickness, undo, save/export PNG. |

## Shared pieces

- `apps/shared/local-store.js` — one small repository for the new stores: list/get/save/
  delete plus `putBlob/getBlob/deleteBlob` for the two content stores. Every row gets an
  id, `createdAt`, `updatedAt`, a `version` counter and a `changed(store)` event so other
  tabs and the search index stay in step.
- `apps/shared/markdown.js` — a deliberately small Markdown renderer (`renderMarkdown`)
  and a text counter (`countText`). It escapes first and emits a fixed tag set: no raw
  HTML, no links, no remote images, no scripts.
- `assets/css/phase8.css` — shared building blocks only (cards, rows, stats, segmented
  control, month grid, stage/crop overlay). Everything inherits design tokens, so dark,
  light, forced-colors and reduced-motion behave like the rest of the shell.

## Data model

IndexedDB moves from **version 4 / 19 stores** to **version 5 / 28 stores**. The
upgrade only creates missing object stores; it never clears or rewrites existing data,
so a Phase 1–7 workspace keeps working unchanged.

New: `habits`, `shopping`, `reading`, `docs`, `focus`, `voice`, `voiceBlobs`,
`sketches`, `sketchBlobs`.

### Backup and restore

`apps/backup/validate.js` grew the store list, per-store validation for the new record
shapes, and a generalised blob descriptor check. `engine.js` now walks a `BLOB_STORES`
list (`fileBlobs`, `voiceBlobs`, `sketchBlobs`) instead of only files: export appends
their bytes in order with per-blob offsets and SHA-256; import verifies boundaries and
digests before rebuilding `Blob`s. Limits stay explicit: 100 files, 200 voice memos,
100 sketches and 50 MiB of file bytes in total.

Compatibility: manifests now carry `schema: 5`. The new stores are **optional** on
import, so an older archive still restores — those stores are emptied, keeping
"restore replaces the workspace" exact. A new archive cannot be imported by an older
build, which is reported as an unsupported schema rather than half-applied.

### Search

`apps/search/model.js` indexes the new collections (Writings, Reading, Habits,
Shopping, Voice notes, Sketches) with the same 10,000-row-per-collection cap and no
file/content reading.

## Icons and tiles

The fourteen new apps each got their own glyph instead of reusing an older one
(Clock used to borrow the kitchen timer, Habits borrowed a plain tick, and so on).

- **One grid, one stroke.** Every glyph is drawn on the same `viewBox="0 0 24 24"`
  as the rest of the set, with `fill: none`, `stroke: currentColor`,
  `stroke-width: 1.65` and round caps/joins — inherited from `svg.icon` in
  `assets/css/tokens.css`, so nothing has to be repeated per icon.
- **Filled accents only where they carry meaning** (the dot of the Focus
  crosshair), declared with `fill="currentColor"` on the element itself.
- **Tile colours.** `assets/css/phase8.css` adds one `linear-gradient(140deg, …)`
  per new app. Before this the new apps had no `.icon-<id>` rule at all, so the
  white glyph sat on a transparent tile and was close to invisible on the light
  theme. The three utility apps from earlier phases that were also missing a
  colour (Find's siblings Controls, Backup and Account) now have one, and
  `.app-icon` in `shell.css` carries a fallback gradient so a future app can
  never ship with an empty tile again.
- **Measured, not guessed.** `tests/phase8_icons.py` reads each glyph's bounding
  box in the live page and asserts it is centred (within 1.8 x / 2.0 y units),
  fills at least 12 of the 24 units, never spills outside the box once the
  stroke is accounted for, and that all fourteen glyphs differ from each other
  and from the older set. It also computes the WCAG contrast of a white glyph
  against both ends of every tile gradient and requires at least 3:1; the Phase 8
  tiles land between 3.05:1 and 3.51:1, which matches the older tiles (median
  3.00:1).

## Where the icons are used

A glyph that only exists in a sprite sheet is not really part of the product, so
`tests/phase8_icon_usage.py` walks the running UI and compares the rendered
markup with `icons.js` at every place an app icon appears:

- home launcher tiles, the four category folders, and the all-apps drawer
- the app titlebar of every Phase 8 app, and its empty state where it has one
  (Focus, Habits, Shopping, Writings, Reading, Voice notes, Photo edit, Sketch)
- the Recently opened row and Find (search) result rows, which take their icon
  from the app registry, so a new glyph flows through automatically
- the Voice notes Record button, which now uses the app's own mic-and-wave glyph

`native/android/app/src/main/assets/public` was regenerated from `app/` with
`scripts/prepare-android.mjs` (the same step `npm run sync` performs), so a
native build carries these icons too. The staging script now reads the version
from `assets/js/config.js` instead of a hard-coded `0.7.0`; it stages 0.8.0.

Two stale labels were corrected on the way: the home heading and drawer footer
still read "Phase 7", and the drawer's app count was a hard-coded 16 — it is now
derived from the registry (34).

## Honest limits

- **No server sync was added for these apps.** PHP/MySQL were not available in the
  environment used to build this, and an untested automatic sync would violate the
  project's rules. The data is local, included in the encrypted backup, and ready for a
  Phase 5-style sync later.
- **Timers are foreground only.** Clock and Focus pause or stop when the app closes,
  when the page hides or when the browser quits; they never fire a background alarm.
- Voice notes need `MediaRecorder` and microphone permission; unsupported browsers say
  so instead of failing silently. On Android the manifest now declares `RECORD_AUDIO`,
  requested by the WebView only when you press Record.
- Photo editing caps images at 6000 px per side to protect memory; saving into Files
  follows the existing 10 MiB per file budget.
- Testing was done in Chromium with synthetic files and a fake capture device. It says
  nothing about real devices, iOS Safari codecs or store builds.
