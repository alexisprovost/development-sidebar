<?php

declare(strict_types=1);

namespace DevelopmentSidebar;

const VERSION = '6.0.0';

const DEFAULTS = [
    'platform'         => 'aws',
    'right_side'       => true,
    'instance_count'   => 1,
    'show_instance'    => true,
    'show_php_version' => true,
    'show_version'     => true,
    'force_show'       => false,
    'task_version'     => null,
    'environment'      => null,
    'hostname'         => null,
    'color'            => null,
    'colors'           => [
        'local'      => '#2ecc40',
        'dev'        => '#ff4136',
        'stg'        => '#ff851b',
        'staging'    => '#ff851b',
        'infra'      => '#0074d9',
        'prod'       => '#111111',
        'production' => '#111111',
    ],
    'local_hosts'      => ['localhost', '127.0.0.1', '::1'],
    'hide_when'        => ['prod', 'production'],
    'csp_nonce'        => null,
    'use_google_fonts' => false,
];

function display(array $options = []): void
{
    echo render($options);
}

function render(array $options = []): string
{
    $opt = _resolve_options($options);
    $env = _resolve_environment($opt);
    if (!_should_display($env, $opt)) {
        return '';
    }

    $color    = _color_for($env, $opt['color'], (array) $opt['colors']);
    $task     = (string) ($opt['task_version'] ?? 'No version was specified in the TASK_VERSION env variable');
    $hostname = _resolve_hostname($opt);
    $tooltip  = _build_tooltip($task, $hostname, $opt);

    $slug       = _slug($env);
    $env_class  = 'devsidebar-env-' . $slug;
    $right      = $opt['right_side'] ? ' devsidebar-right' : '';
    $right_text = $opt['right_side'] ? ' devsidebar-text-right' : '';
    $tip_pos    = $opt['right_side'] ? 'left' : 'right';
    $nonce      = $opt['csp_nonce'] !== null
        ? ' nonce="' . _attr((string) $opt['csp_nonce']) . '"'
        : '';
    $fonts_link = $opt['use_google_fonts']
        ? '<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">'
        : '';

    ob_start();
    ?>
<style<?= $nonce ?>>
.<?= _attr($env_class) ?>{--devsidebar-color:<?= _attr($color) ?>}
<?= CSS ?>
</style><?= $fonts_link ?>
<div class="devsidebar-bar <?= _attr($env_class) ?><?= $right ?>">
    <h3 class="devsidebar-text devsidebar-noselect<?= $right_text ?>">
        <span class="devsidebar-holder">
            <span><?= _html(strtoupper($env)) ?></span>
            <span>—</span>
            <span><?= _html(strtoupper((string) $opt['platform'])) ?></span>
            <span class="devsidebar-spacer">-</span>
        </span>
        <span class="devsidebar-info"
              tabindex="0"
              role="button"
              aria-label="Sidebar info"
              data-tooltip="<?= _attr($tooltip) ?>"
              data-tooltip-pos="<?= _attr($tip_pos) ?>">
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

function _resolve_options(array $options): array
{
    $env_defaults = [];
    $task = getenv('TASK_VERSION');
    if (is_string($task) && $task !== '') {
        $env_defaults['task_version'] = $task;
    }
    $env = getenv('EnvType');
    if (!is_string($env) || $env === '') {
        $env = getenv('ENV_TYPE');
    }
    if (is_string($env) && $env !== '') {
        $env_defaults['environment'] = $env;
    }
    return $options + $env_defaults + DEFAULTS;
}

function _resolve_environment(array $opt): string
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $local = array_map('strtolower', (array) ($opt['local_hosts'] ?? []));
    if ($host !== '' && in_array($host, $local, true)) {
        return 'local';
    }
    if (strtolower((string) ($opt['task_version'] ?? '')) === 'local') {
        return 'local';
    }
    $env = trim(strtolower((string) ($opt['environment'] ?? '')));
    return $env !== '' ? $env : 'unknown';
}

function _resolve_hostname(array $opt): string
{
    $h = $opt['hostname'] ?? gethostname();
    if ($h === false || $h === null || $h === '') {
        $h = 'unknown';
    }
    if ($opt['show_instance']) {
        $h .= ' ( ' . (int) $opt['instance_count'] . ' )';
    }
    return (string) $h;
}

function _should_display(string $env, array $opt): bool
{
    if ($opt['force_show']) {
        return true;
    }
    if (strtolower((string) (getenv('WP_DEBUG') ?: '')) === 'true') {
        return true;
    }
    $hide = array_map('strtolower', (array) ($opt['hide_when'] ?? []));
    return !in_array(strtolower($env), $hide, true);
}

function _build_tooltip(string $task, string $hostname, array $opt): string
{
    $lines = [
        'Informations',
        "\u{2501}\u{2501}\u{2501}\u{2501}\u{2501}\u{2501}",
        'Task Version: ' . $task,
        '',
        'Hostname: ' . $hostname,
    ];
    if ($opt['show_php_version']) {
        $lines[] = '';
        $lines[] = 'PHP Version: ' . PHP_VERSION;
    }
    if ($opt['show_version']) {
        $lines[] = '';
        $lines[] = 'Sidebar Version: ' . VERSION;
    }
    return implode("\n", $lines);
}

function _color_for(string $env, ?string $explicit, array $colors): string
{
    if ($explicit !== null && $explicit !== '') {
        return _safe_color($explicit);
    }
    $key = strtolower($env);
    if (isset($colors[$key]) && is_string($colors[$key])) {
        return _safe_color($colors[$key]);
    }
    return _hash_color($key);
}

function _hash_color(string $name): string
{
    $hue = hexdec(substr(md5($name), 0, 4)) % 360;
    return sprintf('hsl(%d 70%% 42%%)', $hue);
}

function _safe_color(string $color): string
{
    $color = trim($color);
    if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
        return $color;
    }
    if (preg_match('/^(?:hsla?|rgba?)\(\s*[0-9.,%\s\/deg]+\)$/i', $color)) {
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
    background-color: var(--devsidebar-color, #000);
    margin: 0;
    padding: 0;
}

.devsidebar-bar.devsidebar-right { right: 0; }
.devsidebar-bar:not(.devsidebar-right) { left: 0; }

.devsidebar-text {
    top: 50%;
    color: #fff;
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
    background-color: var(--devsidebar-color, #000);
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
    outline: 2px solid #87ff00;
    outline-offset: 2px;
    border-radius: 2px;
}

.devsidebar-info svg {
    fill: #fff;
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
