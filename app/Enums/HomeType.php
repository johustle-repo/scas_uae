<?php

namespace App\Enums;

enum HomeType: string
{
    case Apartment = 'apartment';
    case Villa = 'villa';
    case Townhouse = 'townhouse';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'Apartment',
            self::Villa => 'Villa / house with garden',
            self::Townhouse => 'Townhouse',
            self::Other => 'Other',
        };
    }
}
