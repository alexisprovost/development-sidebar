# Changelog

## 6.0.0

A full rewrite. Same on-screen design, modernized internals, smaller surface.

### Layout

- One source file: `sidebar.php`. Library code, default options, CSS, and JS
  all live there.
- One drop-in entry: `index.php` (10 lines). Requires `sidebar.php`, loads
  optional `config.php`, calls `display()`.
- One bundled artifact: `dist/sidebar.php`, built by `bin/build.php` and
  attached to every tagged release. Single file, no directories needed in
  prod.

### Security

- All output is escaped at the boundary. HTML text, attributes, and JS string
  literals each go through their own escaper. v5 interpolated `TASK_VERSION`
  raw into both a `<script>` block and an `aria-label`, which allowed XSS.
- Local-mode detection no longer trusts an arbitrary `Host` header: it
  matches against a configurable `local_hosts` allow-list (default
  `localhost`, `127.0.0.1`, `::1`).
- `csp_nonce` option applied to emitted `<style>` and `<script>` tags.
- CDN dependencies (Google Fonts, animate.css) are removed by default.
  Google Fonts available as an opt-in flag.

### Versatility

- No hardcoded environment list. Any env name works (`canary`, `qa-eu-west-1`,
  whatever). The bar shows the name uppercased.
- Default colors derived from `md5(env_name)` so each name gets a stable,
  distinguishable color without configuration.
- Per-env color overrides via `colors` map; global color override via
  `color`. Built-in defaults preserve v5 colors for `local` / `dev` / `stg` /
  `staging` / `infra` / `prod` / `production`.
- `hide_when` is configurable: rename your prod env "live" and pass
  `hide_when: ['live']`.

### Modernization

- PHP 8.2 minimum (8.1 EOL'd Dec 2025). Tested on 8.2, 8.3, 8.4 in CI.
- `declare(strict_types=1)`.
- Procedural API: `\DevelopmentSidebar\display(array $options = [])`. No
  classes to instantiate, no enum to map.
- Renderer's CSS root uses `all: revert` so host-page styles cannot bleed in,
  and the JS is wrapped in an IIFE so no globals leak.
- All identifiers prefixed `devsidebar-`. The v5 random `0l14JU` suffix is
  gone.
- `@media print` hides the bar when printing.
- Color is delivered via a CSS custom property (`--devsidebar-color`) scoped
  by an env-derived class, so the static CSS is a constant and only one tiny
  rule is dynamic per request.

### CI

- GitHub Actions workflow lints across PHP 8.2 / 8.3 / 8.4, runs a render
  smoke test, an XSS probe, and a color-hash determinism check.
- On tag push, builds `dist/sidebar.php` and attaches it to the release.

### Removed

- The hardcoded `$bShowSidebarVersion`, `$bNbInstances`, etc. globals.
- `animate.css` and the Google Fonts CDN link (Google Fonts available as
  opt-in).
- The `Environment` enum and PSR-4 class layout from the earlier v6 draft —
  collapsed back into one file for maintenance simplicity.
