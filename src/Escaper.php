<?php

declare(strict_types=1);

namespace DevelopmentSidebar;

final class Escaper
{
    public static function html(string|int|float|bool|null $value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
            'UTF-8'
        );
    }

    public static function attr(string|int|float|bool|null $value): string
    {
        return self::html($value);
    }

    public static function js(mixed $value): string
    {
        $flags = JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR;

        return json_encode($value, $flags);
    }
}
