# DevelopmentSidebar

A small PHP sidebar that shows environment metadata (env type, platform,
hostname, task version, PHP version) on dev / staging / infra deployments.
Hidden in production by default.

Built to be dropped into anything: shared hosting, WordPress, Laravel,
plain PHP. No external network calls. No global JS or CSS leakage.

Requires PHP 8.2 or newer (8.2, 8.3, 8.4, 8.5 are all supported).

## Install

### Option A: Composer

```bash
composer require alexisprovost/development-sidebar
```

Then somewhere late in your response (a footer template, a middleware, etc.):

```php
\DevelopmentSidebar\Sidebar::display();
```

### Option B: Drop-in (no Composer)

Clone or download the repo into your project, then either:

```apache
# .htaccess
php_value auto_append_file "/absolute/path/to/DevelopmentSidebar/index.php"
```

or include it directly:

```php
include '/absolute/path/to/DevelopmentSidebar/index.php';
```

That's it. The `index.php` autoloads the `src/` classes itself if Composer
isn't around.

## Configure

The defaults read from these env vars:

| Env var | Purpose |
| --- | --- |
| `TASK_VERSION` | Build / deploy version shown in the tooltip. |
| `EnvType` (also `ENV_TYPE`) | One of `local`, `dev`, `stg`, `infra`, `prod`. Case-insensitive. |
| `WP_DEBUG` | If `true`, force the bar to show even in prod. |

To override anything else, drop a `config.php` next to `index.php`:

```php
<?php
return new \DevelopmentSidebar\Config(
    platform: 'gcp',
    rightSide: false,
    instanceCount: 3,
    useGoogleFonts: true,
);
```

See `config.example.php` for the full list of options.

When using Composer, pass a `Config` to `Sidebar::display()`:

```php
\DevelopmentSidebar\Sidebar::display(
    new \DevelopmentSidebar\Config(platform: 'azure', forceShow: true)
);
```

## Visibility rules

The bar shows when **any** of these is true:

- The resolved environment is anything other than `prod`.
- `WP_DEBUG=true` is in the environment.
- `Config::forceShow` is `true`.

The resolved environment is `local` if the request `Host` header matches
`Config::localHosts` (default `localhost`, `127.0.0.1`, `::1`) or if
`TASK_VERSION=local`. Otherwise it comes from `EnvType`.

## Security

All output is escaped at the boundary, in the right context (HTML text,
HTML attribute, or JS string). If you set a Content Security Policy with
nonces, pass it via `Config::cspNonce` and the emitted `<style>` / `<script>`
tags will carry it.

## License

MIT. See `LICENSE`.
