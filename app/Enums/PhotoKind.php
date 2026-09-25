<?php

namespace App\Enums;

enum PhotoKind: string
{
    case Profile = 'profile';
    case Rescue = 'rescue';
    case Gallery = 'gallery';

    public function label(): string
    {
        return match ($this) {
            self::Profile => 'Profile photo',
            self::Rescue => 'Rescue / intake photo',
            self::Gallery => 'Gallery photo',
        };
    }
}
