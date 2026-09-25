<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case VaccinationCertificate = 'vaccination_certificate';
    case VeterinaryRecord = 'veterinary_record';
    case Microchip = 'microchip';
    case AdoptionForm = 'adoption_form';
    case FosterAgreement = 'foster_agreement';
    case Travel = 'travel';
    case PetPassport = 'pet_passport';
    case Identification = 'identification';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::VaccinationCertificate => 'Vaccination certificate',
            self::VeterinaryRecord => 'Veterinary record',
            self::Microchip => 'Microchip document',
            self::AdoptionForm => 'Adoption form',
            self::FosterAgreement => 'Foster agreement',
            self::Travel => 'Travel document',
            self::PetPassport => 'Pet passport',
            self::Identification => 'Identification document',
            self::Other => 'Other',
        };
    }
}
