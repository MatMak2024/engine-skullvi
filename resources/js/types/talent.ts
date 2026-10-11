export type TalentSkill = {
    id: string;
    name: string;
    is_required?: boolean;
    level?: string;
};

export type TalentProgramSummary = {
    id: string;
    name: string;
    description: string | null;
    courses_count?: number;
    work_mode?: string;
    work_city?: string | null;
    work_country?: string | null;
};

export type TalentProgram = TalentProgramSummary & {
    slug?: string;
    is_open: boolean;
    skills: TalentSkill[];
    requested_documents?: string[];
};

export type TalentCandidateSkill = {
    id: string;
    name?: string;
    level: string;
};

export type TalentDiploma = {
    id?: string;
    title: string;
    institution: string | null;
    year: number | null;
    description: string | null;
};

export type TalentTraining = {
    id?: string;
    title: string;
    organization: string | null;
    year: number | null;
    description: string | null;
};

export type TalentCandidate = {
    phone: string | null;
    country: string;
    city: string | null;
    has_cv?: boolean;
    education_level?: string | null;
    field_of_study?: string | null;
    years_experience?: number;
    projects_count?: number;
    github_url?: string | null;
    portfolio_url?: string | null;
    linkedin_url?: string | null;
    availability?: string | null;
    interests?: string[];
    other_skills?: { name: string; level: string }[];
    skills?: TalentCandidateSkill[];
    diplomas?: TalentDiploma[];
    trainings?: TalentTraining[];
};

export type TalentStatus =
    | 'received'
    | 'incomplete'
    | 'ineligible'
    | 'priority'
    | 'to_review'
    | 'low_fit'
    | 'accepted'
    | 'rejected';

export type TalentEvaluationScore = {
    criterion: string;
    points: number;
    max_points: number;
    explanation: string;
};

export type TalentEvaluation = {
    total_score: number;
    completeness_rate: number;
    suggested_status: TalentStatus;
    rubric_version: string;
    scores: TalentEvaluationScore[];
};

export type TalentApplicationSummary = {
    id: string;
    status: TalentStatus;
    submitted_at: string | null;
    score: number | null;
    suggested_status: TalentStatus | null;
    program: {
        id: string;
        name: string;
    };
};

export type TalentApplicationDetail = {
    id: string;
    status: TalentStatus;
    submitted_at: string | null;
    education_level: string;
    availability: string;
    motivation_text: string;
    score: number | null;
    suggested_status: TalentStatus | null;
    cv_url: string | null;
    program: {
        id: string;
        name: string;
    };
};

export type RecruiterApplicationSummary = TalentApplicationSummary & {
    examined: boolean;
    candidate: {
        name: string;
        email: string;
    };
};

export type RecruiterFilters = {
    program: string;
    offer: string;
    course: string;
    status: string;
};

export type RecruiterApplicationDetail = {
    id: string;
    status: TalentStatus;
    submitted_at: string | null;
    education_level: string;
    field_of_study: string | null;
    years_experience: number;
    projects_count: number;
    github_url: string | null;
    portfolio_url: string | null;
    linkedin_url: string | null;
    availability: string;
    motivation_text: string;
    cv_url: string | null;
    examined: boolean;
    candidate: {
        name: string;
        email: string;
        phone: string | null;
        country: string;
        city: string | null;
        interests?: string[];
        other_skills?: { name: string; level: string }[];
        diplomas?: TalentDiploma[];
        trainings?: TalentTraining[];
    };
    program: {
        id: string;
        name: string;
    };
    skills: TalentSkill[];
    evaluation: TalentEvaluation | null;
    notes: {
        id: string;
        note: string;
        created_at: string;
        author: string;
    }[];
    documents: {
        id: string;
        label: string;
        url: string;
    }[];
};

export type WorkMode = 'remote' | 'onsite' | 'hybrid';
