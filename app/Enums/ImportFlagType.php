<?php

namespace App\Enums;

enum ImportFlagType: string
{
    case ApproximateDob = 'approximate_dob';
    case PlaceholderMicrochip = 'placeholder_microchip';
    case PossiblePlacementUnconfirmed = 'possible_placement_unconfirmed';
    case IncompleteRecord = 'incomplete_record';

    public function label(): string
    {
        return match ($this) {
            self::ApproximateDob => 'Approximate date of birth',
            self::PlaceholderMicrochip => 'Placeholder microchip number',
            self::PossiblePlacementUnconfirmed => 'Placement status unconfirmed',
            self::IncompleteRecord => 'Incomplete record',
        };
    }
}
