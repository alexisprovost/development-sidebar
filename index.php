<?php

declare(strict_types=1);

/**
 * DevelopmentSidebar v6
 *
 * Drop-in entry. Two install paths:
 *
 *   1. Composer:    composer require alexisprovost/development-sidebar
 *                   then call \DevelopmentSidebar\Sidebar::display();
 *
 *   2. Filesystem:  drop the repo somewhere webroot-readable and add
 *                     php_value auto_append_file "/path/to/DevelopmentSidebar/index.php"
 *                   to .htaccess, or `include` it from your bootstrap.
 *
 * Optional: place a `config.php` next to this file that returns a
 * \DevelopmentSidebar\Config instance to override defaults.
 */

if (\PHP_VERSION_ID < 80200) {
    return;
}

if (defined('DEVELOPMENT_SIDEBAR_LOADED')) {
    return;
}
define('DEVELOPMENT_SIDEBAR_LOADED', true);

(static function (): void {
    $vendorAutoload = __DIR__ . '/vendor/autoload.php';
    if (is_file($vendorAutoload)) {
        require_once $vendorAutoload;
    } elseif (!class_exists(\DevelopmentSidebar\Sidebar::class, false)) {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'DevelopmentSidebar\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
    }

    $config = null;
    $userConfig = __DIR__ . '/config.php';
    if (is_file($userConfig)) {
        $loaded = require $userConfig;
        if ($loaded instanceof \DevelopmentSidebar\Config) {
            $config = $loaded;
        }
    }

    try {
        \DevelopmentSidebar\Sidebar::display($config);
    } catch (\Throwable) {
        // Never break the host page.
    }
})();
