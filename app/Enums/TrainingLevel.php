<?php

namespace App\Enums;

enum TrainingLevel: string
{
    case Untrained = 'untrained';
    case Basic = 'basic';
    case WellTrained = 'well_trained';

    public function label(): string
    {
        return match ($this) {
            self::Untrained => 'Untrained',
            self::Basic => 'Basic',
            self::WellTrained => 'Well Trained',
        };
    }
}
