import { usePage } from '@inertiajs/react';
import {
    Ban,
    Briefcase,
    ChartColumn,
    ClipboardList,
    Contact,
    FileText,
    GraduationCap,
    House,
    LayoutGrid,
    Library,
    UserRound,
    Wrench,
} from 'lucide-react';
import { journeyAccess } from '@/lib/journeys';
import { home } from '@/routes';
import { index as adminPrograms } from '@/routes/admin/programs';
import { index as adminSkills } from '@/routes/admin/skills';
import { index as adminUsers } from '@/routes/admin/users';
import { index as applicationsIndex } from '@/routes/applications';
import { edit as candidateProfile } from '@/routes/candidate-profile';
import type { NavItem } from '@/types';

export type DashboardNavGroup = {
    label: string;
    items: NavItem[];
};

export function useDashboardNavigation(): DashboardNavGroup[] {
    const { talent } = usePage().props;
    const access = journeyAccess(talent);

    const groups: DashboardNavGroup[] = [
        {
            label: 'Navigation',
            items: [
                {
                    title: 'Tableau de bord',
                    href: '/dashboard',
                    icon: LayoutGrid,
                },
                {
                    title: 'Accueil',
                    href: home(),
                    icon: House,
                },
            ],
        },
    ];

    if (access.isUser || access.isCandidate) {
        groups.push({
            label: 'Mon espace',
            items: [
                ...(access.isCandidate
                    ? [
                          {
                              title: 'Candidatures',
                              href: applicationsIndex(),
                              icon: FileText,
                          },
                      ]
                    : []),
                {
                    title: 'Profil',
                    href: candidateProfile(),
                    icon: UserRound,
                },
            ],
        });
    }

    if (access.isSuperAdmin || access.isRecruiter) {
        groups.push({
            label: access.isSuperAdmin ? 'Supervision' : 'Recrutement',
            items: [
                {
                    title: 'Scores',
                    href: '/admin/scores',
                    icon: ChartColumn,
                },
                {
                    title: 'Candidatures',
                    href: '/admin/applications',
                    icon: ClipboardList,
                },
                {
                    title: 'Candidats',
                    href: '/admin/candidates',
                    icon: Contact,
                },
                {
                    title: 'Écartés',
                    href: '/admin/candidates/excluded',
                    icon: Ban,
                },
                {
                    title: 'Cours',
                    href: '/admin/courses',
                    icon: Library,
                },
                {
                    title: 'Offres',
                    href: '/admin/offers',
                    icon: Briefcase,
                },
                {
                    title: 'Programmes',
                    href: adminPrograms(),
                    icon: GraduationCap,
                },
                {
                    title: 'Compétences',
                    href: adminSkills(),
                    icon: Wrench,
                },
                ...(access.isSuperAdmin
                    ? [
                          {
                              title: 'Utilisateurs',
                              href: adminUsers(),
                              icon: UserRound,
                          },
                      ]
                    : []),
            ],
        });
    }

    return groups;
}
