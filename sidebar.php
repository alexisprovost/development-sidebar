<?php

declare(strict_types=1);

namespace DevelopmentSidebar;

const VERSION = '6.0.0';

/**
 * Configuration value object.
 *
 * The recommended way to configure the sidebar (PHP 2026 idiom): a final
 * readonly class with named-argument construction. IDE autocompletes the
 * parameter names, the type checker catches typos, and instances are
 * immutable.
 *
 * Pass-through arrays are also accepted by display() / render() for
 * brevity and for `config.php` files that prefer a plain array.
 */
final readonly class Config
{
    /**
     * @param array<string, string> $colors      Per-env color overrides keyed by lowercase env name.
     * @param list<string>          $localHosts  Hosts that force the env to `local`.
     */
    public function __construct(
        public string $platform = 'aws',
        public bool $rightSide = true,
        public int $instanceCount = 1,
        public bool $showInstance = true,
        public bool $showPhpVersion = true,
        public bool $showVersion = true,
        public ?string $taskVersion = null,
        public ?string $environment = null,
        public ?string $hostname = null,
        public ?string $color = null,
        public ?string $textColor = null,
        public array $colors = [
            'local'      => '#2ecc40',
            'dev'        => '#ff4136',
            'stg'        => '#ff851b',
            'staging'    => '#ff851b',
            'infra'      => '#0074d9',
            'prod'       => '#111111',
            'production' => '#111111',
        ],
        public array $localHosts = ['localhost', '127.0.0.1', '::1'],
        public ?string $cspNonce = null,
        public bool $useGoogleFonts = false,
    ) {
    }
}

function display(Config|array|null $config = null): void
{
    echo render($config);
}

function render(Config|array|null $config = null): string
{
    $cfg      = _resolve_config($config);
    $env      = _resolve_environment($cfg);
    $task     = $cfg->taskVersion ?? 'No version was specified in the TASK_VERSION env variable';
    $hostname = _resolve_hostname($cfg);
    [$bg, $fg] = _resolve_color_pair($env, $cfg);
    $tooltip  = _build_tooltip($task, $hostname, $cfg);

    $slug       = _slug($env);
    $envClass   = 'devsidebar-env-' . $slug;
    $right      = $cfg->rightSide ? ' devsidebar-right' : '';
    $rightText  = $cfg->rightSide ? ' devsidebar-text-right' : '';
    $tipPos     = $cfg->rightSide ? 'left' : 'right';
    $nonce      = $cfg->cspNonce !== null
        ? ' nonce="' . _attr($cfg->cspNonce) . '"'
        : '';
    $fontsLink = $cfg->useGoogleFonts
        ? '<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">'
        : '';

    ob_start();
    ?>
<style<?= $nonce ?>>
.<?= _attr($envClass) ?>{--devsidebar-bg:<?= _attr($bg) ?>;--devsidebar-fg:<?= _attr($fg) ?>}
<?= CSS ?>
</style><?= $fontsLink ?>
<div class="devsidebar-bar <?= _attr($envClass) ?><?= $right ?>">
    <h3 class="devsidebar-text devsidebar-noselect<?= $rightText ?>">
        <span class="devsidebar-holder">
            <span><?= _html(strtoupper($env)) ?></span>
            <span>—</span>
            <span><?= _html(strtoupper($cfg->platform)) ?></span>
            <span class="devsidebar-spacer">-</span>
        </span>
        <span class="devsidebar-info"
              tabindex="0"
              role="button"
              aria-label="Sidebar info"
              data-tooltip="<?= _attr($tooltip) ?>"
              data-tooltip-pos="<?= _attr($tipPos) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true" focusable="false">
                <path d="M256 90c44.3 0 86 17.3 117.4 48.6C404.7 170 422 211.7 422 256s-17.3 86-48.6 117.4C342 404.7 300.3 422 256 422s-86-17.3-117.4-48.6C107.3 342 90 300.3 90 256s17.3-86 48.6-117.4C170 107.3 211.7 90 256 90m0-42C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48z"/>
                <path d="M277 360h-42V235h42v125zm0-166h-42v-42h42v42z"/>
            </svg>
        </span>
    </h3>
</div>
<script<?= $nonce ?>>(function(){var taskVersion=<?= _js($task) ?>;<?= JS ?>})();</script>
    <?php
    return (string) ob_get_clean();
}

function _resolve_config(Config|array|null $config): Config
{
    if ($config instanceof Config) {
        return $config;
    }

    $envDefaults = [];
    $task = getenv('TASK_VERSION');
    if (is_string($task) && $task !== '') {
        $envDefaults['taskVersion'] = $task;
    }
    $env = getenv('EnvType');
    if (!is_string($env) || $env === '') {
        $env = getenv('ENV_TYPE');
    }
    if (is_string($env) && $env !== '') {
        $envDefaults['environment'] = $env;
    }

    $merged = ($config ?? []) + $envDefaults;
    return new Config(...$merged);
}

function _resolve_environment(Config $cfg): string
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $local = array_map('strtolower', $cfg->localHosts);
    if ($host !== '' && in_array($host, $local, true)) {
        return 'local';
    }
    if (strtolower((string) ($cfg->taskVersion ?? '')) === 'local') {
        return 'local';
    }
    $env = trim(strtolower((string) ($cfg->environment ?? '')));
    return $env !== '' ? $env : 'unknown';
}

function _resolve_hostname(Config $cfg): string
{
    $h = $cfg->hostname ?? gethostname();
    if ($h === false || $h === null || $h === '') {
        $h = 'unknown';
    }
    if ($cfg->showInstance) {
        $h .= ' ( ' . $cfg->instanceCount . ' )';
    }
    return (string) $h;
}

function _build_tooltip(string $task, string $hostname, Config $cfg): string
{
    $lines = [
        'Informations',
        "\u{2501}\u{2501}\u{2501}\u{2501}\u{2501}\u{2501}",
        'Task Version: ' . $task,
        '',
        'Hostname: ' . $hostname,
    ];
    if ($cfg->showPhpVersion) {
        $lines[] = '';
        $lines[] = 'PHP Version: ' . PHP_VERSION;
    }
    if ($cfg->showVersion) {
        $lines[] = '';
        $lines[] = 'Sidebar Version: ' . VERSION;
    }
    return implode("\n", $lines);
}

/**
 * @return array{0: string, 1: string} [background, text]
 */
function _resolve_color_pair(string $env, Config $cfg): array
{
    if ($cfg->color !== null && $cfg->color !== '') {
        $bg = _safe_color($cfg->color);
        $fg = $cfg->textColor !== null ? _safe_color($cfg->textColor) : _contrast_text($bg);
        return [$bg, $fg];
    }
    $key = strtolower($env);
    if (isset($cfg->colors[$key]) && is_string($cfg->colors[$key])) {
        $bg = _safe_color($cfg->colors[$key]);
        $fg = $cfg->textColor !== null ? _safe_color($cfg->textColor) : _contrast_text($bg);
        return [$bg, $fg];
    }
    $bg = _hash_color($key);
    // Hash colors are produced at OKLCH lightness 0.58 — always paired with white text.
    $fg = $cfg->textColor !== null ? _safe_color($cfg->textColor) : '#fff';
    return [$bg, $fg];
}

/**
 * Deterministic, perceptually uniform color from any env name.
 *
 * Uses OKLCH (CSS Color Level 4, supported by every browser since 2023). At
 * fixed lightness 0.58 and chroma 0.16, all hues look equally bright and
 * equally vivid — none of the muddy yellows or washed-out blues you get
 * from HSL.
 */
function _hash_color(string $name): string
{
    $hue = hexdec(substr(md5($name), 0, 6)) % 360;
    return sprintf('oklch(0.58 0.16 %d)', $hue);
}

/**
 * Pick a foreground color (#fff or #111) based on WCAG relative luminance
 * of the background. Falls back to white for non-hex inputs.
 */
function _contrast_text(string $color): string
{
    $rgb = _parse_hex($color);
    if ($rgb === null) {
        return '#fff';
    }
    [$r, $g, $b] = $rgb;
    return _wcag_luminance($r, $g, $b) > 0.55 ? '#111' : '#fff';
}

/**
 * @return array{0: int, 1: int, 2: int}|null
 */
function _parse_hex(string $color): ?array
{
    if (preg_match('/^#([0-9a-fA-F]{3})$/', $color, $m)) {
        $h = $m[1];
        return [
            hexdec($h[0] . $h[0]),
            hexdec($h[1] . $h[1]),
            hexdec($h[2] . $h[2]),
        ];
    }
    if (preg_match('/^#([0-9a-fA-F]{6})$/', $color, $m)) {
        return [
            hexdec(substr($m[1], 0, 2)),
            hexdec(substr($m[1], 2, 2)),
            hexdec(substr($m[1], 4, 2)),
        ];
    }
    return null;
}

function _wcag_luminance(int $r, int $g, int $b): float
{
    $channel = static function (int $c): float {
        $v = $c / 255.0;
        return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    };
    return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
}

function _safe_color(string $color): string
{
    $color = trim($color);
    if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
        return $color;
    }
    if (preg_match('/^(?:hsla?|rgba?|oklch|oklab|lab|lch|hwb|color)\([^()]{1,200}\)$/i', $color)) {
        return $color;
    }
    if (preg_match('/^[a-zA-Z]{1,40}$/', $color)) {
        return $color;
    }
    return '#000';
}

function _slug(string $s): string
{
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9-]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? $s : 'unknown';
}

function _html(string|int|float|bool|null $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function _attr(string|int|float|bool|null $v): string
{
    return _html($v);
}

function _js(mixed $v): string
{
    return json_encode(
        $v,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
}

const CSS = <<<'CSS'
.devsidebar-bar,
.devsidebar-bar *,
.devsidebar-bar *::before,
.devsidebar-bar *::after {
    all: revert;
    box-sizing: border-box;
    font-family: Poppins, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.devsidebar-bar {
    top: 0;
    width: 6px;
    height: 100%;
    z-index: 2147483000;
    position: fixed;
    background-color: var(--devsidebar-bg, #000);
    margin: 0;
    padding: 0;
}

.devsidebar-bar.devsidebar-right { right: 0; }
.devsidebar-bar:not(.devsidebar-right) { left: 0; }

.devsidebar-text {
    top: 50%;
    color: var(--devsidebar-fg, #fff);
    padding: 5px;
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    line-height: 1.2;
    position: absolute;
    text-align: center;
    word-break: break-all;
    text-transform: uppercase;
    border-radius: 0 5px 5px 0;
    transform: perspective(1px) translateY(-50%);
    background-color: var(--devsidebar-bg, #000);
}

.devsidebar-text.devsidebar-text-right {
    right: 0;
    border-radius: 5px 0 0 5px;
}

.devsidebar-holder {
    display: block;
    letter-spacing: 6px;
    margin-right: -4px;
}

.devsidebar-holder > span {
    display: block;
    text-align: center;
}

.devsidebar-holder > .devsidebar-spacer {
    opacity: 0;
    font-size: 5px;
    height: 5px;
    line-height: 5px;
}

.devsidebar-noselect {
    user-select: none;
    -webkit-user-select: none;
    cursor: default;
}

.devsidebar-info {
    display: inline-block;
    font-size: 0;
    text-align: left;
    text-transform: none;
    cursor: pointer;
    position: relative;
    border: 0;
    padding: 0;
    margin: 0;
    background: transparent;
    outline: none;
}

.devsidebar-info:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
    border-radius: 2px;
}

.devsidebar-info svg {
    fill: var(--devsidebar-fg, #fff);
    width: 18px;
    height: 18px;
    transition: fill 0.18s, transform 0.18s;
}

.devsidebar-info.devsidebar-pulse svg {
    fill: #87ff00;
    animation: devsidebar-pulse 1.4s ease-in-out infinite;
}

@keyframes devsidebar-pulse {
    0%, 100% { transform: scale(1); }
    50%      { transform: scale(1.18); }
}

.devsidebar-info[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.18s ease-out 0.18s, transform 0.18s ease-out 0.18s;
    background: rgba(16, 16, 16, 0.95);
    color: #fff;
    padding: 0.5em 1em;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.4;
    white-space: pre;
    text-transform: none;
    letter-spacing: normal;
    z-index: 2147483001;
    text-align: left;
}

.devsidebar-info[data-tooltip-pos="left"]::after {
    right: 100%;
    top: 50%;
    margin-right: 10px;
    transform: translate(4px, -50%);
}

.devsidebar-info[data-tooltip-pos="right"]::after {
    left: 100%;
    top: 50%;
    margin-left: 10px;
    transform: translate(-4px, -50%);
}

.devsidebar-info[data-tooltip]:hover::after,
.devsidebar-info[data-tooltip]:focus-visible::after {
    opacity: 1;
    transform: translate(0, -50%);
}

@media print {
    .devsidebar-bar { display: none !important; }
}
CSS;

const JS = <<<'JS'
var nodes = document.querySelectorAll('.devsidebar-info');
var icon = nodes[nodes.length - 1];
if (icon) {
    var KEY = 'devsidebar.task';

    icon.addEventListener('click', function (e) {
        e.preventDefault();
    }, false);

    var stored = '';
    try {
        stored = localStorage.getItem(KEY) || '';
    } catch (_) {}

    if (taskVersion && stored !== taskVersion) {
        icon.classList.add('devsidebar-pulse');
    }

    var ack = function (e) {
        if (e) {
            e.preventDefault();
        }
        try {
            localStorage.setItem(KEY, taskVersion);
        } catch (_) {}
        icon.classList.remove('devsidebar-pulse');
    };

    icon.addEventListener('contextmenu', ack, false);
    icon.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            ack(e);
        }
    }, false);
}
JS;
