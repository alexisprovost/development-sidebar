<?php

declare(strict_types=1);

/**
 * Copy this file to `config.php` next to index.php to override defaults.
 * Return a \DevelopmentSidebar\Config instance.
 *
 * Every option below is optional and shown with its default value.
 */

return new \DevelopmentSidebar\Config(
    platform: 'aws',
    rightSide: true,
    showInstanceCount: true,
    instanceCount: 1,
    showPhpVersion: true,
    showSidebarVersion: true,
    forceShow: false,
    sidebarVersion: '6.0.0',
    // taskVersion: read from TASK_VERSION env var by default
    // environment: read from EnvType / ENV_TYPE env var by default
    // hostname:    read from gethostname() by default
    localHosts: ['localhost', '127.0.0.1', '::1'],
    cspNonce: null,        // set to your per-request CSP nonce if you use one
    useGoogleFonts: false, // true to load Poppins from fonts.googleapis.com
);
