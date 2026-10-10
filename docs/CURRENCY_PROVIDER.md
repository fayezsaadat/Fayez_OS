# Optional currency provider

## Default: off, manual conversion available

`config/app.php` reads `CURRENCY_PROVIDER`; unset/`disabled` means no external
request. Currency → Provider rate → Fetch then returns HTTP 503 with
`PROVIDER_NOT_CONFIGURED`. That is an intentional configuration state, not a fake
successful rate. Manual conversion works without PHP, credentials or internet
once the app is loaded and browser storage is available.

The only supported adapter in this release is `erapi`, using:

```text
https://open.er-api.com/v6/latest/USD
```

It is a fixed HTTPS host, path and base currency. No client URL, amount, currency
parameter, contact or other personal record is forwarded upstream.

## Enable in XAMPP / Apache

1. In the **Apache-loaded** `php.ini`, enable PHP cURL (`extension=curl`, or the
   matching Windows extension directive for your XAMPP installation).
2. Use a working trusted CA bundle. If needed configure `curl.cainfo` to its
   absolute path. **Never disable TLS verification** to work around certificates.
3. Set this in your local VirtualHost/server environment (`mod_env`):

```apache
SetEnv CURRENCY_PROVIDER erapi
```

4. Give the Apache/PHP service account write access to `storage/cache/`. On Unix,
   prefer owner-only directory permissions and the service account as owner; on
   Windows use restrictive ACLs. Do not make the entire project world-writable.
5. Restart Apache. `api/health.php` should report
   `currency_provider_configured: true`. This flag confirms configuration, not
   upstream reachability, cURL availability or writable cache permissions.
6. Open Currency → Provider rate → Fetch provider rates. A successful response
   shows the provider date/source. Convert only after reviewing that provenance.

No API key is needed for this open-access adapter. If changing to a paid provider
later, keep any key on the server; do not put it in `config.js` or Android assets.

Optional local PHP CLI launch:

```sh
CURRENCY_PROVIDER=erapi php -S 0.0.0.0:8080 -t . dev-router.php
```

In PowerShell, set `$env:CURRENCY_PROVIDER='erapi'` before launching PHP instead.
Use this server only for local development, never production hosting.

## JSON endpoint

`GET /api/currency/rates.php` accepts **no query parameters**. Uses the standard
`{success, message, data, code?}` envelope. On success, `data` has:

```text
base: "USD"
rates: nine positive plain-decimal strings, keyed by supported currency code
source: "ExchangeRate-API (open access)"
asOf: provider's update timestamp
fetchedAt: timestamp of the upstream fetch
cached: true if served from the PHP cache
```

No illustrative price values are supplied here as market data. Frontend quote
validation checks the base, all nine codes, positive bounded precision, attribution
and timestamps. Upstream payloads must have `result: success`, base USD and a
one-to-one USD base rate.

| HTTP/code | Meaning |
|---|---|
| 400 `INVALID_PARAMETERS` | Query parameters rejected |
| 405 `METHOD_NOT_ALLOWED` | Only GET accepted |
| 503 `PROVIDER_NOT_CONFIGURED` | Adapter disabled/unsupported |
| 503 `PROVIDER_UNAVAILABLE` | PHP cURL missing |
| 503 `CACHE_UNAVAILABLE` | PHP cache directory/file not writable |
| 503 `PROVIDER_BUSY` | Another request holds refresh lock; retry later |
| 429 `RATE_LIMITED` | Local 60-second attempt cooldown |
| 502 `PROVIDER_ERROR` | Transport/status/payload/cache-write failure |

The browser reports errors and retains previously validated saved rates. It never
labels those as newly fetched after a failure. Saved/historical data is for
reference only; neither this API nor the frontend guarantees transaction prices.

## Cache, limits and server hardening

- Public quote cache: `storage/cache/currency-usd.json`, one-hour server TTL.
- Nonblocking file lock prevents simultaneous upstream fetches; a 60-second
  cooldown limits repeated failed refreshes. `Retry-After` is returned where useful.
- Atomic temp-file rename and owner-only cache-file permissions where supported.
- `storage/.htaccess` denies HTTP access; `dev-router.php` does likewise. For
  Nginx/IIS or another document root, add equivalent explicit deny rules.
- cURL verifies HTTPS peer/host; redirects are disabled. Connection timeout 3s,
  total timeout 6s, response body capped at 256 KiB.
- Client responses are `no-store`; the PWA service worker excludes APIs. The
  browser separately saves a validated quote in IndexedDB for dated offline use.
- Only public rates enter the server cache. Runtime cache/lock/cooldown files are
  excluded from the release ZIP and source control.
- This is a converter adapter, **not a general-purpose rate redistribution API**.
  Before public production deployment add web-server request throttling, monitoring
  and egress controls. The single-host lock/cooldown is not distributed abuse/DDoS
  protection, authentication or a security certification.

## Attribution and provider terms

The Currency screen includes the attribution link **Rates By Exchange Rate API**.
Keep it when using this provider. Its open-access documentation describes daily
updates, rate limiting and permitted caching, and disallows redistribution.
Review the current [open-access documentation](https://www.exchangerate-api.com/docs/free)
and [terms](https://www.exchangerate-api.com/terms) before deployment; terms and
service availability can change. Documentation reviewed 28 September 2026.

Do not label daily indicative rates as a live market feed. Local exchange shops,
banks, fees and spreads can differ materially from the provider's reference data.

## Tests

```sh
php tests/provider_test.php          # deterministic synthetic fixtures
php tests/provider_test.php --live   # optional real upstream request; needs cURL
python tests/api_smoke.py            # default-disabled HTTP server and protections
```

The development validation actually fetched and normalized all nine supported
codes on 28 September 2026, with provider update timestamp
`2026-09-28T00:02:31+00:00`. The exact endpoint was also executed via PHP CLI with
`CURRENCY_PROVIDER=erapi`: first request fetched/persisted, second served identical
cached data. No fetched rate values or browser personal records are shipped.
A further real browser test on the enabled development HTTP server passed
Fetch, source/date rendering, conversion/history and server-cache reuse without
a response mock. The Phase 3 preview enabled this adapter through its process environment only;
the ZIP default remains disabled. A later preview may use different environment
settings. This does not verify
your XAMPP firewall, CA setup or production provider uptime.
