<?php

declare(strict_types=1);

namespace App\Bootstrap;

final class ApplicationBootstrap
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    public function boot(): Configuration
    {
        $configuration = Configuration::fromEnvironment($this->projectRoot . '/config/app.php');
        date_default_timezone_set($configuration->timezone());

        return $configuration;
    }
}
