<?php

namespace App\Enums;

enum Size: string
{
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Giant = 'giant';

    public function label(): string
    {
        return match ($this) {
            self::Small => 'Small',
            self::Medium => 'Medium',
            self::Large => 'Large',
            self::Giant => 'Giant',
        };
    }
}
