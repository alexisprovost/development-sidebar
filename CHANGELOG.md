# Changelog

## 6.0.0

A full rewrite. Same on-screen design, modernized internals.

### Security

- All output is escaped at the boundary. HTML text, attributes, and JS string
  literals each go through their own escaper. The previous version interpolated
  `$bTaskVersion` directly into a `<script>` block and an `aria-label`
  attribute, which allowed XSS via the `TASK_VERSION` env var.
- The host detection no longer trusts `$_SERVER['HTTP_HOST']` blindly. Local
  hosts are matched against a configurable allow-list (default
  `localhost`, `127.0.0.1`, `::1`).
- A CSP nonce can be supplied via `Config::cspNonce` and is applied to the
  emitted `<style>` and `<script>` tags.
- The CDN dependencies (Google Fonts, animate.css) are removed by default. A
  system font stack is used unless `useGoogleFonts` is enabled.

### Modernization

- Requires PHP 8.2 or newer (8.2, 8.3, 8.4, 8.5 supported).
- Splits into namespaced classes: `Config` (readonly), `Environment` (enum),
  `Renderer`, `Escaper`, `Sidebar` (facade).
- Installable two ways: `composer require alexisprovost/development-sidebar`,
  or drop the repo in place and point `auto_append_file` at `index.php`.
- Optional `config.php` next to `index.php` to override defaults without
  editing source.
- `declare(strict_types=1)` everywhere.
- The renderer wraps its CSS root in `all: revert` so host-page styles cannot
  bleed into the bar, and its JS in an IIFE so no globals leak.
- `print` media query hides the bar when printing.
- All identifiers prefixed `devsidebar-`. The previous random `0l14JU` suffix
  is gone.

### Behavior preserved

- Same default visual: thin black bar on the right, vertical letter-spaced
  text, info icon with hover tooltip.
- Same color scheme for `dev` / `stg` / `infra`.
- Same defaults: hidden in prod, shown elsewhere or when `WP_DEBUG=true`.
- Same env vars consumed: `TASK_VERSION`, `EnvType` (also accepts `ENV_TYPE`),
  `WP_DEBUG`.

### Removed

- The hardcoded `$bNbInstances`, `$showBar`, `$bShowSidebarVersion` etc.
  globals are now `Config` properties.
- `animate.css` and Google Fonts CDN links (Google Fonts available as opt-in).
