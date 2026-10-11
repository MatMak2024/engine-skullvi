import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageFrame } from '@/components/talent/page-frame';
import {
    SkillChoices,
    type SkillOption,
} from '@/components/talent/skill-choices';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { fieldClassName } from '@/lib/talent';

type CourseFormData = {
    id: string;
    name: string;
    description: string | null;
    is_open: boolean;
    program_id?: string | null;
    required_skills: string[];
    bonus_skills: string[];
    requested_documents?: string[];
};

export default function AdminCourseForm({
    course,
    skills,
    programs = [],
}: {
    course: CourseFormData | null;
    skills: SkillOption[];
    programs?: { id: string; name: string }[];
}) {
    const action = course ? `/admin/courses/${course.id}` : '/admin/courses';

    return (
        <>
            <Head title={course ? course.name : 'Nouveau cours'} />
            <PageFrame
                eyebrow="Recrutement"
                title={course ? 'Modifier le cours' : 'Nouveau cours'}
                description="Un cours peut rester seul ou entrer dans un programme. Ses compétences prérequises servent au score."
            >
                <Form
                    action={action}
                    method={course ? 'put' : 'post'}
                    className="grid max-w-2xl gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={course?.name ?? ''}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="program_id">Programme</Label>
                                <select
                                    id="program_id"
                                    name="program_id"
                                    defaultValue={course?.program_id ?? ''}
                                    className={fieldClassName}
                                >
                                    <option value="">Aucun programme</option>
                                    {programs.map((program) => (
                                        <option key={program.id} value={program.id}>
                                            {program.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.program_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    defaultValue={course?.description ?? ''}
                                    rows={4}
                                    className="border-input w-full rounded-xl border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="requested_documents">
                                    Documents de qualification demandés
                                </Label>
                                <textarea
                                    id="requested_documents"
                                    name="requested_documents"
                                    defaultValue={(
                                        course?.requested_documents ?? []
                                    ).join('\n')}
                                    rows={3}
                                    placeholder="Un document par ligne, par exemple Diplôme"
                                    className="border-input w-full rounded-xl border bg-transparent px-3 py-2 text-sm text-[#0e2a2e] shadow-xs outline-none focus-visible:ring-[3px]"
                                />
                                <InputError message={errors.requested_documents} />
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="is_open"
                                    value="1"
                                    defaultChecked={course?.is_open ?? true}
                                    className="size-4"
                                />
                                Cours ouvert aux candidatures
                            </label>
                            <SkillChoices
                                title="Compétences prérequises"
                                name="required_skills[]"
                                skills={skills}
                                selected={course?.required_skills ?? []}
                                error={errors.required_skills}
                            />
                            <SkillChoices
                                title="Compétences bonus"
                                name="bonus_skills[]"
                                skills={skills}
                                selected={course?.bonus_skills ?? []}
                                error={errors.bonus_skills}
                            />
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Enregistrer
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/courses">Retour</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </PageFrame>
        </>
    );
}

AdminCourseForm.layout = {
    breadcrumbs: [
        { title: 'Cours', href: '/admin/courses' },
        { title: 'Formulaire', href: '/admin/courses/create' },
    ],
};
