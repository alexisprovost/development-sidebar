# DevelopmentSidebar

A tiny PHP sidebar that shows environment metadata (env name, platform,
hostname, task version, PHP version) on dev / staging / infra deployments.
Hidden in prod by default.

One source file. Drop it in via `auto_append_file`, install via Composer,
or grab the bundled single-file release artifact. Same widget either way.

Requires PHP 8.2 or newer (8.2 / 8.3 / 8.4 / 8.5 supported).

## Install

### A. Composer

```bash
composer require alexisprovost/development-sidebar
```

Then call this somewhere late in your response (footer template, middleware):

```php
\DevelopmentSidebar\display();
```

With overrides:

```php
\DevelopmentSidebar\display([
    'platform'    => 'gcp',
    'environment' => 'canary',
    'force_show'  => true,
]);
```

### B. Drop-in (no Composer)

Clone or download the repo, then in `.htaccess`:

```apache
php_value auto_append_file "/absolute/path/to/DevelopmentSidebar/index.php"
```

Or `include '/absolute/path/to/DevelopmentSidebar/index.php';` from your
bootstrap. `index.php` requires `sidebar.php`, reads an optional `config.php`
next to it, and renders.

### C. Single-file release artifact

Each tagged release attaches `sidebar.php` (the library + the auto-display
block bundled into one file). Download it from
[Releases](https://github.com/alexisprovost/DevelopmentSidebar/releases),
drop it on the server, point `auto_append_file` at it. Done. One file, no
directories.

## Configure

Defaults read from these env vars:

| Env var | Purpose |
| --- | --- |
| `TASK_VERSION` | Build / deploy version shown in the tooltip. |
| `EnvType` (also `ENV_TYPE`) | Any string. Becomes the env name shown in the bar. |
| `WP_DEBUG` | If `true`, force the bar to show even in prod. |

Pass options to `display()` (Composer) or via a `config.php` next to
`index.php` (drop-in) that returns an array:

```php
<?php
return [
    'platform'    => 'gcp',
    'right_side'  => false,
    'environment' => 'canary',
    'colors'      => ['canary' => '#9c27b0', 'qa' => '#ff5722'],
    'hide_when'   => ['production', 'live'],
];
```

### Options

| Key | Default | Meaning |
| --- | --- | --- |
| `platform` | `'aws'` | Shown next to the env name. Free-form. |
| `right_side` | `true` | Anchor on the right edge of the viewport. |
| `instance_count` | `1` | Number shown after the hostname. |
| `show_instance` | `true` | Render the instance count. |
| `show_php_version` | `true` | Render PHP version in the tooltip. |
| `show_version` | `true` | Render the sidebar version in the tooltip. |
| `force_show` | `false` | Show even when env is in `hide_when`. |
| `task_version` | `getenv('TASK_VERSION')` | Build / deploy version. |
| `environment` | `getenv('EnvType') ?: getenv('ENV_TYPE')` | Free-form env name. |
| `hostname` | `gethostname()` | Hostname shown in the tooltip. |
| `color` | `null` | Override the bar color globally (any CSS color). |
| `colors` | `[local, dev, stg, infra, prod]` map | Per-env color overrides. |
| `local_hosts` | `['localhost', '127.0.0.1', '::1']` | Hosts that force `environment = local`. |
| `hide_when` | `['prod', 'production']` | Env names where the bar stays hidden. |
| `csp_nonce` | `null` | If set, applied to emitted `<style>` and `<script>`. |
| `use_google_fonts` | `false` | Loads Poppins from Google Fonts when `true`. |

## Colors

Any env name works. By default `local`, `dev`, `stg`, `staging`, `infra`,
`prod`, `production` map to fixed colors (matching v5). For everything else
the bar derives a color from `md5(env_name)` so:

- The same env name always gets the same color.
- Different env names get visibly different colors.

Override anything via `color` (single global override) or `colors`
(per-env map). Values must be `#hex`, `rgb(...)`, `rgba(...)`, `hsl(...)`,
`hsla(...)`, or a CSS named color.

## Visibility

The bar shows when **any** of these is true:

- The resolved env is not in `hide_when`.
- `WP_DEBUG=true` is in the environment.
- `force_show` is `true`.

The resolved env is `local` if the request `Host` header matches
`local_hosts`, or if `TASK_VERSION=local`. Otherwise it comes from
`environment` (or env vars).

## Security

All output is escaped at the boundary, in the right context: HTML text via
`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5)`, JS string
literals via `json_encode` with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
| JSON_HEX_QUOT`. The escaped JS literal cannot break out of `<script>` even
when the input contains `</script>` or unescaped quotes.

If you set a Content Security Policy with nonces, pass it via `csp_nonce`
and the emitted `<style>` / `<script>` tags will carry it.

## Build (maintainers)

```bash
php bin/build.php
# writes dist/sidebar.php
```

CI does this automatically on tag push and attaches the bundled file to the
GitHub Release.

## License

MIT. See `LICENSE`.
