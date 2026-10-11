import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { PageFrame } from '@/components/talent/page-frame';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { fieldClassName } from '@/lib/talent';

type SkillFormData = {
    id: string;
    name: string;
    category_id: string | null;
    category: string | null;
    programs_count: number;
    courses_count: number;
    offers_count: number;
    applications_count: number;
};

export default function AdminSkillForm({
    skill,
    categories = [],
}: {
    skill: SkillFormData | null;
    categories?: { id: string; name: string }[];
}) {
    const [categoryId, setCategoryId] = useState(skill?.category_id ?? '');
    const inUse =
        (skill?.programs_count ?? 0) > 0 ||
        (skill?.courses_count ?? 0) > 0 ||
        (skill?.offers_count ?? 0) > 0 ||
        (skill?.applications_count ?? 0) > 0;

    return (
        <>
            <Head title={skill ? skill.name : 'Nouvelle compétence'} />
            <PageFrame
                eyebrow="Administration"
                title={skill ? 'Modifier la compétence' : 'Nouvelle compétence'}
                description="Le nom doit être unique. La catégorie regroupe les compétences du même domaine."
            >
                <Form
                    action={skill ? `/admin/skills/${skill.id}` : '/admin/skills'}
                    method={skill ? 'put' : 'post'}
                    className="grid max-w-xl gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={skill?.name ?? ''}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="category_id">Catégorie</Label>
                                <select
                                    id="category_id"
                                    name="category_id"
                                    value={categoryId}
                                    onChange={(event) =>
                                        setCategoryId(event.target.value)
                                    }
                                    className={fieldClassName}
                                >
                                    <option value="">Nouvelle catégorie</option>
                                    {categories.map((category) => (
                                        <option
                                            key={category.id}
                                            value={category.id}
                                        >
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.category_id} />
                            </div>
                            {categoryId === '' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="category">
                                        Nom de la nouvelle catégorie
                                    </Label>
                                    <Input
                                        id="category"
                                        name="category"
                                        required
                                        defaultValue=""
                                        placeholder="Collège, Lycée, Formations"
                                    />
                                    <InputError message={errors.category} />
                                </div>
                            )}
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Enregistrer
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/skills">Retour</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
                {skill && (
                    <Form action={`/admin/skills/${skill.id}`} method="delete">
                        {({ processing, errors }) => (
                            <div className="mt-8 grid gap-2">
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing || inUse}
                                >
                                    Supprimer
                                </Button>
                                <InputError message={errors.skill} />
                            </div>
                        )}
                    </Form>
                )}
            </PageFrame>
        </>
    );
}

AdminSkillForm.layout = {
    breadcrumbs: [
        { title: 'Compétences', href: '/admin/skills' },
        { title: 'Formulaire', href: '/admin/skills/create' },
    ],
};
