# Phase 4 provider administration

## Requirements and defaults

PHP 8+ with **cURL** and a working trusted CA bundle. News also requires the PHP
**DOM/XML** extension. `storage/cache/` must be writable by the PHP service account
and denied to HTTP clients. Keep restrictive ACLs/ownership; do not make the whole
project world-writable. Restart Apache after extension/environment changes.

This personal-use release defaults to:

```apache
SetEnv WEATHER_PROVIDER openmeteo
SetEnv NEWS_PROVIDER bbc
```

Both are free-to-access, no-key endpoints. Set either value to `disabled` to stop
that integration; its UI then reports provider-not-configured (503). News requests
occur on explicit Refresh. Weather fetches the selected city when the home screen
loads or returns to the foreground if no saved forecast exists or the latest one is
at least 15 minutes old. Opening Weather also refreshes a missing/stale snapshot;
manual Refresh/Search remain available. There is no periodic/background fetch and
no device-location request unless the user presses **Use my location**. Currency
retains its separate Phase 3 default-disabled `CURRENCY_PROVIDER` setting.

Review current provider terms **before public/commercial deployment**. Availability,
quotas and licenses can change. Do not treat no-key access as permission for any
redistribution or commercial use. This is not a provider SLA or legal review.

## Weather

Fixed HTTPS hosts and paths:

- `https://api.open-meteo.com/v1/forecast`
- `https://geocoding-api.open-meteo.com/v1/search`

Routes, all GET-only:

| Route | Allowed query | Behavior |
|---|---|---|
| `api/weather/forecast.php` | `lat`, `lon` | Validate bounds, round to 2 decimals, request fixed variables and 7 days |
| `api/weather/locations.php` | `q` | Explicit city search, 2–120 UTF-8 bytes, maximum 8 results |

All extra query parameters are rejected. No client may supply an upstream URL,
host, arbitrary weather variables, redirect or API key. The frontend has one API
base in `app/assets/js/config.js`, including for Android reuse.

The forecast response is normalized to `source`, `timezone`, `current`, `hours`
(up to 24), `days` (7), `fetchedAt`, `serverCached`. Times are Unix instants;
weather-location timezone is explicit. Values are numeric/bounded or explicitly
null when optional and unavailable; no missing metric is fabricated as zero.

The UI attributes Open-Meteo and GeoNames. Review:

- [Forecast documentation](https://open-meteo.com/en/docs)
- [Geocoding documentation](https://open-meteo.com/en/docs/geocoding-api)
- [Open-Meteo terms](https://open-meteo.com/en/terms)
- [Open-Meteo pricing / commercial access](https://open-meteo.com/en/pricing)

The free API is intended for noncommercial use under its current service terms;
commercial deployment needs the appropriate agreement/adapter configuration.
Data attribution must be retained. This release does not implement a paid
customer API/key adapter.

### Location privacy

Device location is optional and only requested by a labeled button. Refresh sends
rounded coordinates to PHP and the provider; city Search sends the entered name.
The provider sees the server's IP and normal request metadata. Server access logs
may contain city/coordinate query strings. Avoid unnecessarily retaining those
logs. Public forecast caches are protected, but are not an encrypted personal
location vault. No geolocation is shared by merely opening Weather.

## News

Fixed allowlisted RSS feeds, chosen through `api/news/feed.php?feed=world|persian`:

- `https://feeds.bbci.co.uk/news/world/rss.xml`
- `https://feeds.bbci.co.uk/persian/rss.xml`

RSS is parsed server-side with network access disabled in XML parsing. DTD/entity
input is rejected. At most 40 unique items: ID, plain title, summary, canonical
HTTPS BBC source URL, publisher, feed and publication date (nullable).
Publisher links must use exact BBC hosts without credentials/ports. Query and
fragment tracking is discarded. No third-party article images or full-page HTML
are sent to the frontend.

This is a personal feed reader with visible publisher attribution and links, not
an unrestricted syndication service. Keep links/attribution and review the
[BBC terms](https://www.bbc.co.uk/usingthebbc/terms/), including rules for feeds,
content reuse and commercial publication, before deploying publicly. Publisher
rights remain with the publisher. Do not scrape or bypass restricted full text.
Disabling News must remain possible if your deployment lacks permission.

## Shared transport/cache safeguards

- TLS peer/host verification on; HTTPS only; redirects off.
- Connection timeout 3 seconds, overall timeout 6 seconds; upstream body <=512 KiB.
- Browser API timeout 8 seconds and cancellable requests.
- Weather and news cache TTL 15 minutes; place search TTL 24 hours.
- Protected disk files, hashed query keys, atomic temp-file replacement and
  namespace refresh lock. At most 64 cached keys per namespace; oldest evicted.
- Two-second namespace cooldown between uncached refresh attempts. Cache hits do
  not consume a new upstream request. HTTP 429/503 include `Retry-After: 2` for
  local cooldown/busy conditions.
- Cache files contain provider data, not selected files or QR contents. Runtime
  caches/locks/attempt markers are excluded from the release ZIP.
- Responses use `{success, message, data, code?}` and `Cache-Control: no-store`.
  Service workers do not cache these routes; browser apps explicitly save their
  validated snapshots for offline reference.

### HTTP failures

400 for bad/missing query values or unsupported feed; 405 for wrong method;
503 `PROVIDER_NOT_CONFIGURED` for disabled providers; 429 `RATE_LIMITED` for local
cooldown; 503 `PROVIDER_BUSY` for concurrent refresh; 502 `PROVIDER_ERROR` for
transport, schema, extension or cache failures. Server details are logged, not
exposed as PHP traces. Users keep an explicitly dated existing local snapshot.

These are lightweight **single-host** safeguards, not distributed rate limiting,
user authentication, DDoS protection or production certification. For a public
service add reverse-proxy per-client throttling, egress controls, cache quotas,
operational monitoring, privacy-aware logging and appropriate provider agreements.
Do not expose this adapter as an independent data-redistribution service.

## Hosting/browser policy

`app/.htaccess` allows same-origin camera and geolocation, disables microphone,
and allows local Blob images/media while keeping `object-src 'none'` and only
local executable scripts. The development router explicitly serves the HTML entry
with equivalent policies (PHP's static handler otherwise discards router headers).
Apache must enable `mod_headers` and the appropriate AllowOverride policy. Nginx
and IIS need equivalent rules and explicit private-directory denial.

Camera/location require HTTPS or localhost and browser permission. An embedding
iframe/ancestor policy may still deny them; the app explains alternatives.
Do not disable TLS validation or use wildcard CORS to bypass configuration errors.

## Verify

```sh
php tests/phase4_provider_test.php         # deterministic schema/security fixtures
php tests/phase4_provider_test.php --live  # actual upstream calls
python tests/phase4_api_smoke.py           # HTTP validation + security headers
```

On 28 September 2026 the real Open-Meteo response, World RSS and Persian RSS were
fetched and validated, in addition to synthetic failure/security fixtures. This
does not verify a user's XAMPP CA/firewall, provider uptime or production capacity.

## Terms review — 28 September 2026

The Open-Meteo terms and BBC reuse/RSS page were actually reviewed for this release:

- https://open-meteo.com/en/terms — free service is non-commercial, with limits of
  fewer than 10,000 daily, 5,000 hourly and 600 minute calls; data uses CC-BY 4.0.
  Subscriptions/ads/commercial product use require an appropriate paid plan.
  Open-Meteo says troubleshooting logs can include coordinates and are retained
  for up to 90 days. Rounded location is **not** anonymous location.
- https://www.bbc.co.uk/usingthebbc/terms/can-i-use-bbc-content/ — individual RSS
  website reuse has attribution, unchanged-feed and branding conditions;
  business RSS/metadata use requires permission/licensing and may involve a fee.
  This adapter normalizes/caps fields and omits remote images. **Do not assume its
  summary format automatically satisfies unmodified-feed redistribution terms.**
  For public/commercial publication obtain the needed permission, choose a suitably
  licensed adapter, or set `NEWS_PROVIDER=disabled`.

This personal-use development integration is not legal clearance, a commercial
license, or a guarantee that future provider terms remain unchanged. The cache
cooldown is not a complete provider daily/monthly quota budget or production
rate-limiter. Recheck terms and add deployment-level limits before public access.
