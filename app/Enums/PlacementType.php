<?php

namespace App\Enums;

enum PlacementType: string
{
    case Local = 'local';
    case International = 'international';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local',
            self::International => 'International',
        };
    }
}
