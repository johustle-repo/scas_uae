<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case RecordsManager = 'records_manager';
    case MedicalStaff = 'medical_staff';
    case PlacementCoordinator = 'placement_coordinator';
    case ReadOnly = 'read_only';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::RecordsManager => 'Records Manager',
            self::MedicalStaff => 'Medical / Veterinary Staff',
            self::PlacementCoordinator => 'Adoption / Foster Coordinator',
            self::ReadOnly => 'Read-Only User',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full access, including users and system logs.',
            self::RecordsManager => 'Manages all dog records, medical and placement details, and documents.',
            self::MedicalStaff => 'Manages medical records, treatments and medical documents.',
            self::PlacementCoordinator => 'Manages foster and adoption placements and their documents.',
            self::ReadOnly => 'Can view records but cannot change anything.',
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::RecordsManager => [
                Permission::ManageDogs,
                Permission::ManageMedical,
                Permission::ManagePlacements,
                Permission::ManageDocuments,
            ],
            self::MedicalStaff => [Permission::ManageMedical, Permission::ManageDocuments],
            self::PlacementCoordinator => [Permission::ManagePlacements, Permission::ManageDocuments],
            self::ReadOnly => [],
        };
    }

    public function allows(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
