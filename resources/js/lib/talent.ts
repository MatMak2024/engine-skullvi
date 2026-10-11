import type { TalentStatus } from '@/types';

export const statusLabels: Record<TalentStatus, string> = {
    received: 'Reçue',
    incomplete: 'Incomplète',
    ineligible: 'Non éligible',
    priority: 'Prioritaire',
    to_review: 'À examiner',
    low_fit: 'Adéquation faible',
    accepted: 'Acceptée',
    rejected: 'Refusée',
};

export const educationLabels: Record<string, string> = {
    none: 'Sans diplôme',
    bac: 'Bac',
    licence: 'Licence',
    master: 'Master',
    other: 'Autre',
};

export const availabilityLabels: Record<string, string> = {
    immediate: 'Immédiate',
    one_month: 'Sous un mois',
    later: 'Plus tard',
};

export const skillLevelLabels: Record<string, string> = {
    beginner: 'Débutant',
    intermediate: 'Intermédiaire',
    advanced: 'Avancé',
};

export const criterionLabels: Record<string, string> = {
    skills: 'Compétences',
    experience: 'Expérience',
    education: 'Formation',
    proofs: 'Preuves',
    motivation: 'Motivation',
    availability: 'Disponibilité',
    location: 'Localisation',
};

export const workModeLabels: Record<string, string> = {
    remote: 'Télétravail',
    onsite: 'Sur place',
    hybrid: 'Hybride',
};

export function workPlaceLabel(
    mode: string | null | undefined,
    city: string | null | undefined,
    country: string | null | undefined,
): string {
    const label = workModeLabels[mode ?? 'remote'] ?? 'Télétravail';
    const place = [city, country].filter(Boolean).join(', ');

    if (!place || mode === 'remote') {
        return label;
    }

    return `${label} · ${place}`;
}

export const fieldClassName =
    'border-input bg-background h-9 w-full rounded-xl border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export const textareaClassName =
    'border-input bg-background min-h-32 w-full rounded-xl border px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export function statusVariant(
    status: TalentStatus,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (status === 'priority' || status === 'accepted') {
        return 'default';
    }

    if (status === 'rejected' || status === 'ineligible') {
        return 'destructive';
    }

    if (status === 'to_review' || status === 'received') {
        return 'secondary';
    }

    return 'outline';
}
