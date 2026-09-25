<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case NotStarted = 'not_started';
    case Pending = 'pending';
    case Registered = 'registered';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::Pending => 'Pending',
            self::Registered => 'Registered',
            self::Expired => 'Expired',
        };
    }
}
