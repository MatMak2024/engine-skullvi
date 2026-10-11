import { Badge } from '@/components/ui/badge';
import { statusLabels, statusVariant } from '@/lib/talent';
import type { TalentStatus } from '@/types';

export function StatusBadge({ status }: { status: TalentStatus }) {
    return <Badge variant={statusVariant(status)}>{statusLabels[status]}</Badge>;
}
