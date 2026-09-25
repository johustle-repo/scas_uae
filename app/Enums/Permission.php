<?php

namespace App\Enums;

/**
 * Abilities granted to user roles. Each value is registered as a Gate ability,
 * so routes can use e.g. `->can('manage-dogs')`.
 */
enum Permission: string
{
    /** Create, edit and archive dog profiles, identification and rescue details. */
    case ManageDogs = 'manage-dogs';

    /** Edit medical profiles and treatment/vaccination history. */
    case ManageMedical = 'manage-medical';

    /** Add, edit and close foster placements and adoption records. */
    case ManagePlacements = 'manage-placements';

    /** Upload, replace and remove documents and photos. */
    case ManageDocuments = 'manage-documents';

    /** Add and edit user accounts. */
    case ManageUsers = 'manage-users';

    /** View the system audit log. */
    case ViewAuditLog = 'view-audit-log';
}
