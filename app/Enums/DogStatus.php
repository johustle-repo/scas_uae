<?php

namespace App\Enums;

enum DogStatus: string
{
    case AtScas = 'at_scas';
    case LocalFoster = 'local_foster';
    case InternationalFoster = 'international_foster';
    case AdoptedUae = 'adopted_uae';
    case AdoptedInternationally = 'adopted_internationally';
    case ReturnedRehoming = 'returned_rehoming';
    case Memorial = 'memorial';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::AtScas => 'At SCAS',
            self::LocalFoster => 'Local Foster',
            self::InternationalFoster => 'International Foster',
            self::AdoptedUae => 'Adopted in UAE',
            self::AdoptedInternationally => 'Adopted Internationally',
            self::ReturnedRehoming => 'Returned / Rehoming',
            self::Memorial => 'Memorial',
            self::Archived => 'Archived',
        };
    }
}
