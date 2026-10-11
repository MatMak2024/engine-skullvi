export type TalentAccess = {
    isCandidate: boolean;
    roles: string[];
};

export function journeyAccess(talent: TalentAccess) {
    const roles = talent.roles ?? [];
    const isSuperAdmin = roles.includes('super_admin');
    const isRecruiter = roles.includes('recruiter') && !isSuperAdmin;
    const isCandidate = talent.isCandidate || roles.includes('candidate');
    const isUser = !isSuperAdmin && !isRecruiter && !isCandidate;

    return { isSuperAdmin, isRecruiter, isCandidate, isUser };
}
