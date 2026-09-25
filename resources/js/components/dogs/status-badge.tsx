import { Badge, type BadgeTone } from '@/components/ui/badge';
import { DOG_STATUS_LABELS, type DogStatus } from '@/types/dog';

const toneByStatus: Record<DogStatus, BadgeTone> = {
    at_scas: 'info',
    local_foster: 'brand',
    international_foster: 'brand',
    adopted_uae: 'success',
    adopted_internationally: 'success',
    returned_rehoming: 'warning',
    memorial: 'neutral',
    archived: 'neutral',
};

export function StatusBadge({ status }: { status: DogStatus }) {
    return (
        <Badge tone={toneByStatus[status]}>{DOG_STATUS_LABELS[status]}</Badge>
    );
}
