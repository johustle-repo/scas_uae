<?php

namespace App\Enums;

enum Species: string
{
    case Canine = 'canine';
    case CaninePuppy = 'canine_puppy';

    public function label(): string
    {
        return match ($this) {
            self::Canine => 'Canine',
            self::CaninePuppy => 'Canine (Puppy)',
        };
    }
}
