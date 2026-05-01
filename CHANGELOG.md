# Changelog

## 6.0.0

A full rewrite. Same on-screen design, modernized internals, smaller surface.

### Configuration: PHP 2026 idiom

The recommended way to configure the sidebar is now a `final readonly`
`Config` class with named-argument construction:

```php
\DevelopmentSidebar\display(new \DevelopmentSidebar\Config(
    platform:    'gcp',
    environment: 'canary',
));
```

IDE autocomplete picks up every parameter, the type checker catches typos
(an unknown key like `colour` errors at parse time instead of being
silently ignored), and instances are immutable. Arrays still work for
brevity and `config.php` files; both forms produce identical output.

### Visibility model

The bar always renders when `sidebar.php` is loaded. v5 had an in-PHP
`hide_when prod` check that still loaded the file in prod just to suppress
the output. v6 drops that — visibility is decided at the deployment layer:

- Don't `auto_append_file` it on prod hosts.
- Don't `composer require` it on prod hosts (or wrap the `display()` call
  in your own env check).

This is correct for opcache, caching layers, and `auto_append_file`
ordering, and it removes three options (`hide_when`, `force_show`,
`WP_DEBUG`) that v5 used to paper over the wrong model.

### Colors

- **OKLCH for hash colors.** `md5(env_name) → hue` mapped to
  `oklch(0.58 0.16 hue)`. OKLCH is perceptually uniform, so every hue
  comes out at the same brightness. HSL was producing washed-out yellows
  and over-bright greens; OKLCH does not.
- **Auto-contrast text color.** Text color is computed from WCAG relative
  luminance of the background — white text on dark backgrounds, near-black
  on light. Override with `textColor` if you want.
- **Any env name works.** Built-in colors for `local`, `dev`, `stg` /
  `staging`, `infra`, `prod` / `production`. Anything else gets a stable
  OKLCH-derived color.
- Bar background and text are delivered via two CSS custom properties
  (`--devsidebar-bg`, `--devsidebar-fg`) scoped by an env-derived class,
  so the static CSS stays a constant and only one tiny rule is dynamic per
  request.

### Layout

- One source file: `sidebar.php`. Library code, `Config` class, defaults,
  CSS, and JS all live there.
- One drop-in entry: `index.php` (~25 lines).
- One bundled artifact: `dist/sidebar.php`, built by `bin/build.php` and
  attached to every tagged release. Single file, no directories needed in
  prod.

### Security

- All output is escaped at the boundary. v5 interpolated `TASK_VERSION`
  raw into both a `<script>` block and an `aria-label`, which allowed XSS.
- Local-mode detection no longer trusts an arbitrary `Host` header: it
  matches against a configurable `localHosts` allow-list.
- `cspNonce` option applied to emitted `<style>` and `<script>` tags.

### CI

- Lints across PHP 8.2 / 8.3 / 8.4 / 8.5.
- Runs an XSS probe, a smoke render, an OKLCH hash determinism check, and
  an auto-contrast check on every push.
- On tag push or manual `workflow_dispatch`, builds `dist/sidebar.php`
  and attaches it to the GitHub Release / uploads as a workflow artifact.
- Dependabot keeps the action versions current monthly.

### Removed

- `hide_when`, `force_show`, `WP_DEBUG` (visibility is now a deployment
  concern, not a render-time concern).
- `animate.css` and the Google Fonts CDN link (Google Fonts available as
  opt-in).
- The hardcoded `$bShowSidebarVersion`, `$bNbInstances`, etc. globals.
