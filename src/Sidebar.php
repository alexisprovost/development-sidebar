<?php

declare(strict_types=1);

namespace DevelopmentSidebar;

final class Sidebar
{
    public static function render(?Config $config = null): string
    {
        return (new Renderer($config ?? Config::fromEnvironment()))->render();
    }

    public static function display(?Config $config = null): void
    {
        echo self::render($config);
    }
}
