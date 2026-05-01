# DevelopmentSidebar

[![CI](https://github.com/alexisprovost/DevelopmentSidebar/actions/workflows/ci.yml/badge.svg)](https://github.com/alexisprovost/DevelopmentSidebar/actions/workflows/ci.yml)

A tiny PHP sidebar that pins the env name, platform, hostname, and build version to the edge of every page on dev / staging / infra. One file. PHP 8.2+. No dependencies.

![sidebar](https://img.sshort.net/i/LqJ3.png)

## Install

The bar shows whenever `sidebar.php` is loaded. To keep it off prod, simply don't load it on prod servers — your apache / nginx / php-fpm config decides that.

### Apache (`auto_append_file`)

```apache
# .htaccess on dev / staging / infra only
php_value auto_append_file "/var/www/sidebar.php"
```

### nginx + php-fpm

Pool config (recommended — set once per pool):

```ini
; /etc/php/8.4/fpm/pool.d/dev.conf  (only on dev / staging / infra hosts)
php_value[auto_append_file] = /var/www/sidebar.php
```

Or per-server in nginx:

```nginx
location ~ \.php$ {
    fastcgi_param PHP_VALUE "auto_append_file=/var/www/sidebar.php";
    # ... usual fastcgi_pass etc.
}
```

### `php.ini` directly

```ini
auto_append_file = "/var/www/sidebar.php"
```

### Composer

```bash
composer require alexisprovost/development-sidebar
```

Then in your layout:

```php
\DevelopmentSidebar\display();
```

### Single-file release

Grab `sidebar.php` from the [latest release](https://github.com/alexisprovost/DevelopmentSidebar/releases) and use any of the four methods above.

## Configure

Modern PHP (recommended) — pass a `Config` object with named arguments, IDE autocomplete picks up every parameter:

```php
\DevelopmentSidebar\display(new \DevelopmentSidebar\Config(
    platform:    'gcp',
    environment: 'canary',
    color:       '#9c27b0',
));
```

Or pass an array if you prefer:

```php
\DevelopmentSidebar\display([
    'platform'    => 'gcp',
    'environment' => 'canary',
]);
```

For the `auto_append_file` path, drop a `config.php` next to `index.php`. It can return either form:

```php
<?php
return new \DevelopmentSidebar\Config(
    platform: 'gcp',
    colors:   ['canary' => '#9c27b0', 'qa' => '#ff5722'],
);
```

## Env vars

These are picked up automatically if you don't override them:

| Var | Purpose |
| --- | --- |
| `TASK_VERSION` | Build / deploy version shown in the tooltip. |
| `EnvType` (or `ENV_TYPE`) | The env name shown in the bar (any string). |

## Colors

Any env name works. Built-ins for `local`, `dev`, `stg` / `staging`, `infra`, `prod` / `production` keep v5's palette. Anything else gets a stable color derived from `md5(env_name)` rendered in [OKLCH](https://oklch.com/) — perceptually uniform, so all hues come out equally bright and clean.

The text color is computed automatically: dark backgrounds get white text, light backgrounds get near-black text (WCAG relative luminance threshold). Override with `textColor` if you want.

## All options

All `Config` parameters (defaults shown):

| Parameter | Default | Notes |
| --- | --- | --- |
| `platform` | `'aws'` | Free-form text. |
| `rightSide` | `true` | `false` to anchor on the left. |
| `instanceCount` | `1` | Number after the hostname. |
| `showInstance` | `true` | Render the instance count. |
| `showPhpVersion` | `true` | Show in tooltip. |
| `showVersion` | `true` | Show sidebar version in tooltip. |
| `taskVersion` | `getenv('TASK_VERSION')` | |
| `environment` | `getenv('EnvType') \|\| getenv('ENV_TYPE')` | Free-form. |
| `hostname` | `gethostname()` | |
| `color` | `null` | Single global background override (any CSS color). |
| `textColor` | `null` (auto-contrast) | Force a specific text color. |
| `colors` | v5 palette | Per-env background overrides. |
| `localHosts` | `['localhost', '127.0.0.1', '::1']` | Hosts that force `environment = local`. |
| `cspNonce` | `null` | Applied to `<style>` and `<script>`. |
| `useGoogleFonts` | `false` | Loads Poppins from Google Fonts. |

## Visibility

The bar always renders when `sidebar.php` is loaded. Decide visibility at the deployment layer:

- Don't `auto_append_file` it on prod hosts.
- Don't `composer require` it on prod hosts (or wrap the `display()` call in your own env check).

This is simpler than the v5 in-PHP `prod` check (which still loaded the file in prod, just to hide it), and it's the only model that's correct for caching layers, opcache, and `auto_append_file` execution order.

## Security

All output is escaped at the boundary. The `<script>` block embeds the task version via `json_encode` with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, so user-controlled bytes can't break out of the tag even when they contain `</script>` or unescaped quotes. CI runs an XSS probe against `EnvType` and `TASK_VERSION` on every push.

## Build (maintainers)

```bash
php bin/build.php
# writes dist/sidebar.php (minified bundle, ~13 KB)
```

CI does this automatically on tag push and attaches the file to the GitHub Release. Manual builds via `Actions → CI → Run workflow` upload the bundle as a workflow artifact (30-day retention).

## License

MIT.
