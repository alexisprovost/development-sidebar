# DevelopmentSidebar

[![CI](https://github.com/alexisprovost/DevelopmentSidebar/actions/workflows/ci.yml/badge.svg)](https://github.com/alexisprovost/DevelopmentSidebar/actions/workflows/ci.yml)

A tiny PHP sidebar that shows the env name, platform, hostname, task version, and PHP version on dev / staging / infra. Hidden in prod by default. One file. PHP 8.2+.

![sidebar](https://img.sshort.net/i/LqJ3.png)

## Install

Pick the one that fits.

### Single file (simplest, infra friendly)

Download `sidebar.php` from the [latest release](https://github.com/alexisprovost/DevelopmentSidebar/releases), drop it on the server, point Apache at it:

```apache
# .htaccess
php_value auto_append_file "/var/www/sidebar.php"
```

Done. The bar will render on every page.

### Composer

```bash
composer require alexisprovost/development-sidebar
```

Then somewhere in your layout:

```php
\DevelopmentSidebar\display();
```

### Clone the repo

```apache
php_value auto_append_file "/path/to/DevelopmentSidebar/index.php"
```

## Configure

Set these env vars and the bar picks them up automatically:

```bash
EnvType=stg              # or dev, infra, canary, qa, anything
TASK_VERSION=v1.2.3      # build / deploy version, shown in tooltip
WP_DEBUG=true            # force-show the bar even in prod
```

Need more control? Pass an array (Composer):

```php
\DevelopmentSidebar\display([
    'platform'    => 'gcp',
    'environment' => 'canary',
    'force_show'  => true,
]);
```

Or, for the drop-in path, drop a `config.php` next to `index.php`:

```php
<?php
return [
    'platform'  => 'gcp',
    'colors'    => ['canary' => '#9c27b0', 'qa' => '#ff5722'],
    'hide_when' => ['production', 'live'],
];
```

## Colors

Any env name works. Built-in defaults match v5 for `local`, `dev`, `stg` (and `staging`), `infra`, `prod` (and `production`). Anything else gets a stable color derived from `md5(env_name)`. Override per-env via `colors`, or globally via `color`.

## Options

| Key | Default | Notes |
| --- | --- | --- |
| `platform` | `'aws'` | Free-form text shown next to the env. |
| `right_side` | `true` | Anchor right or left. |
| `instance_count` | `1` | Number after the hostname. |
| `show_instance` | `true` | Render the instance count. |
| `show_php_version` | `true` | Show in tooltip. |
| `show_version` | `true` | Show sidebar version in tooltip. |
| `force_show` | `false` | Bypass the hide check. |
| `task_version` | `getenv('TASK_VERSION')` | |
| `environment` | `getenv('EnvType') ?: getenv('ENV_TYPE')` | Free-form. |
| `hostname` | `gethostname()` | |
| `color` | `null` | Single global color override (any CSS color). |
| `colors` | v5 map | Per-env overrides. |
| `local_hosts` | `[localhost, 127.0.0.1, ::1]` | Hosts that force `environment = local`. |
| `hide_when` | `[prod, production]` | Env names where the bar stays hidden. |
| `csp_nonce` | `null` | Applied to `<style>` and `<script>`. |
| `use_google_fonts` | `false` | Loads Poppins from Google Fonts. |

## Hidden in prod

By default the bar is hidden when the env is `prod` or `production`. Override with `hide_when` if your prod is called something else.

## Security

All output is escaped at the boundary. The JS string literal is encoded with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` so user-controlled bytes can't break out of `<script>`. CI runs an XSS probe on every push.

## Build (maintainers)

```bash
php bin/build.php   # writes dist/sidebar.php (minified bundle)
```

CI does this automatically on tag push and attaches the file to the GitHub Release.

## License

MIT.
