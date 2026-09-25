<?php

namespace App\Enums;

enum AdoptionApplicationStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Approved = 'approved';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Approved => 'Approved',
            self::Declined => 'Declined',
        };
    }
}
