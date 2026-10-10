> Historical Phase 3 scope. Current release is Phase 4 / 0.4.0. Use PHASE_4_FA.md, docs/PHASE_4.md and INSTALLATION.md for current upgrade instructions.

# Phase 3 — money, currencies and contacts

Version 0.3.0 adds three working apps to the existing shell. It does not implement
Phase 4 feeds/files or Phase 5 accounts/cloud. The seven delivered personal apps
are local-first. The only new server endpoint is an optional public-rate adapter.

## Expenses

- Income/expense create, edit and confirmed delete; category, Gregorian date,
  currency, payment method and description. Metadata includes UUID, timestamps,
  optimistic version and deletion tombstone.
- Monthly income, expenses and balance; separate all-time balance. Changing the
  ledger currency never mixes or silently converts records.
- A spending budget per `YYYY-MM:currency`. Zero means a no-spending budget;
  unset is different from zero. Remaining/over-budget totals use expenses only.
- Real monthly day-range charts and category breakdown, including accessible
  exact-figure tables. February/leap-year ranges respect the month's length.
- Search, type/category/payment filters, this-month/all-history date scope and
  20-row incremental display. Dashboard totals remain the complete month/ledger,
  independent of history filters.
- Export the filtered ledger to quoted UTF-8 CSV with BOM and formula-prefix
  protection. This is **not** a full backup or a restore/import mechanism.
- One recoverable transaction draft. Closing an untouched new editor does not
  create an empty draft. Changes survive Back/reload; Save commits the record.
  A stale edit is rejected; **Save as new** creates an explicit separate copy.
- Budget changes require Save; Cancel does not save an unfinished budget.

### Money representation

`apps/shared/money.js` accepts ASCII, Persian and Arabic digits, a decimal point
(or Arabic decimal separator), and correctly grouped thousands. Amounts support
12 integer and 2 fractional digits. Transactions must be positive; converter
amounts and budgets may be zero. No signs, exponent notation or implicit rounding
of entered transaction amounts.

Persist amounts as **integer minor-unit decimal strings**, for example
`amountMinor: "125050"` for 1,250.50. Sums use BigInt, not floating-point addition.
All nine supported currencies use this app's two-decimal bookkeeping convention;
this is not a cash-denomination, tax, bank or accounting standard implementation.

Transaction fields:

```text
id, type, amountMinor, currency, category, date, paymentMethod, description,
createdAt, updatedAt, version, deletedAt
```

Budget fields: `id = month:currency`, `month`, `currency`, `amountMinor` (null when
removed), `version`, creation/update timestamps. The repository enforces unique
keys and optimistic updates, including across tabs.

## Currency

AFN, USD, EUR, GBP, IRR, PKR, TRY, AED and SAR.

### Manual

No rate is prefilled or fabricated. Enter the amount and your own rate, then
Convert. The screen and history label it **Manual**. Changing the currency pair
clears the manual rate; Swap reverses the pair and retains an exact reciprocal,
even though its text is displayed to at most 12 decimal places. Editing that text
replaces the retained reciprocal with the entered value. The same currency uses
an explicit identity rate of one and does not need a provider.

Ratios are positive numerator/denominator strings. Cross-rates and inversions use
BigInt arithmetic. Only the final output is rounded, half-up, to two decimals.
Entered rates support at most 12 integer and 12 fractional digits. PHP provider
numbers are normalized to up to 12 fractional digits; exact frontend calculation
cannot recover precision absent from the upstream quote.

### Provider

Explicit Fetch calls the central JSON client and configured PHP adapter. It is
**disabled by default**. Missing configuration, offline state, timeouts and
upstream failures are visible, not replaced with sample data.

Every provider calculation carries a source and source timestamp. Freshly fetched,
saved, historical and offline quotes are distinguished. Quotes older than 24 hours
are marked. This is daily indicative reference data, **not a live tradable price**;
fees, spreads and local cash-market differences are excluded. Dates are displayed
in the device's timezone; stored instants are ISO timestamps.

A valid last quote is saved in IndexedDB for explicit offline use. The service
worker itself never caches API responses. A failed fetch does not silently invent
a quote. Users may continue with the clearly labeled saved quote or manual mode.
See [CURRENCY_PROVIDER.md](CURRENCY_PROVIDER.md) for administration and terms.

### History and consistency

The newest 100 conversions are retained. Each stores original amount/result,
from/to codes, the exact ratio and its provenance. Tap an entry to reuse its
original rate; it is not silently repriced as current. Clear history confirms and
keeps input preferences and the saved rate cache. Inputs persist across reload.

The calculation is snapshotted before asynchronous storage. Changing the inputs
while a save is pending cannot mutate the history entry into another conversion.
Fetch cancellation/tokens prevent a late response from replacing an abandoned
screen. Changing source or using Swap/history cancels the previous fetch.

## Contacts

- Add, edit, confirmed delete, A–Z library, All/Favorites/category filters and
  debounced Unicode search. Arabic/Persian variants of ی and ک are normalized.
- Required name up to 150 characters; optional phone (40), email (254), address
  (1,000) and free-form category (40). Name-only entries are allowed. Default
  category is Personal. Favorite is a boolean.
- Phone digits normalize to ASCII. Call opens a sanitized `tel:` link; Email
  opens an encoded `mailto:` link. The device must have a supporting handler;
  neither control automatically places a call or sends a message.
- Optional local JPEG/PNG/WebP up to 2 MB. MIME/signature, decode and dimension
  checks; maximum decoded dimensions 4096 × 4096. Crop/re-encode to a 160 × 160
  JPEG; only bounded JPEG data URLs are retained. SVG, remote photo URLs and
  server uploads are not accepted. Remove photo returns to initials.
- Desktop split list/detail; phone detail with internal Back to list, then Back
  to the shell. Editor drafts and optimistic conflict-copy handling match Expenses.
- No device address-book permission, contact import, cloud sync or remote avatars.

## Storage upgrade and privacy

Database `fayez-os` is now **version 3**, adding `transactions`, `budgets`,
`contacts`, `conversions` and `exchangeRates`. It retains the seven previous
stores: `preferences`, `notes`, `tasks`, `outbox`, `events`, `calculations`,
`drafts`. v1 and populated v2 upgrades are tested without deleting old records.

Data belongs to the browser origin, not an authenticated user. It is **unencrypted**
and accessible to same-origin scripts. Clearing site data, uninstalling a PWA
with its data, or losing the browser profile can remove it. Drafts also have a
small synchronous localStorage cache. Do not store irreplaceable sensitive data
without an independent record. Complete backup/restore remains Phase 6.

Soft-deleted transaction/contact records retain tombstones; Delete removes them
from the app but is not a secure disk-erasure operation. No personal record,
conversion amount or contact photo is sent to the provider or PHP/MySQL. The
outbox remains unused pending account ownership and explicit import consent.

## Runtime files

- `apps/shared/money.js`: decimal parsing, integer money and rational conversion.
- `apps/expenses/model.js`: transaction rules, monthly aggregation.
- `apps/expenses/repository.js`: budgets and safe CSV export.
- `apps/expenses/index.js`: dashboard/history/editor.
- `apps/currency/repository.js`: quote validation/cache and capped history.
- `apps/currency/index.js`: converter, provenance, cancellation, preferences.
- `apps/contacts/avatar.js`: local image processing and URL allowlist.
- `apps/contacts/index.js`: library/detail/editor and field validation.
- `assets/css/phase3.css`: shared-token responsive app styling.
- `api/currency/rates.php`, `includes/currency-provider.php`: optional provider.

All paths above are under `app/` unless explicitly under `api/` or `includes/`.
No Node, framework or build step is required to run this release.
