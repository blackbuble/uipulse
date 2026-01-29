<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AiSettings extends Settings
{
    public string $default_provider;
    public array $providers;

    public static function group(): string
    {
        return 'ai';
    }
}
