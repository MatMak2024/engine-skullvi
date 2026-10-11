import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Ban, Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { PageFrame } from '@/components/talent/page-frame';
import { Pagination } from '@/components/talent/pagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Page } from '@/types';

type SkillRow = {
    id: string;
    name: string;
    is_open: boolean;
    category: string | null;
    programs_count: number;
    courses_count: number;
    offers_count: number;
    applications_count: number;
};

export default function AdminSkillsIndex({
    skills,
    canManage = false,
}: {
    skills: Page<SkillRow>;
    canManage?: boolean;
}) {
    const { errors } = usePage().props;

    return (
        <>
            <Head title="Compétences" />
            <PageFrame
                eyebrow="Recrutement"
                title="Compétences"
                description="Créez une compétence, suspendez-la pour qu’elle ne puisse plus être ajoutée, ou retirez-la si elle n’est pas utilisée."
                actions={
                    canManage ? (
                        <Button asChild>
                            <Link href="/admin/skills/create">
                                Nouvelle compétence
                            </Link>
                        </Button>
                    ) : null
                }
            >
                <InputError message={errors.skill} />
                {canManage && (
                    <div className="flex justify-end">
                        <Button size="icon" variant="outline" asChild>
                            <Link
                                href="/admin/skills/create"
                                aria-label="Créer une compétence"
                                title="Créer une compétence"
                            >
                                <Plus />
                            </Link>
                        </Button>
                    </div>
                )}
                {skills.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Aucune compétence pour le moment.
                    </p>
                ) : (
                    <ul className="divide-y overflow-hidden rounded-3xl border bg-card">
                        {skills.data.map((skill) => {
                            const blocked =
                                skill.programs_count > 0 ||
                                skill.courses_count > 0 ||
                                skill.offers_count > 0 ||
                                skill.applications_count > 0;
                            const details = [
                                skill.category ?? 'Sans catégorie',
                                `${skill.courses_count} cours`,
                                `${skill.offers_count} offre${skill.offers_count > 1 ? 's' : ''}`,
                                `${skill.programs_count} programme${skill.programs_count > 1 ? 's' : ''}`,
                                skill.is_open ? null : 'Suspendue',
                            ].filter(Boolean);

                            return (
                                <li
                                    key={skill.id}
                                    className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium text-foreground">
                                            {skill.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {details.join(' · ')}
                                        </p>
                                    </div>
                                    {canManage && (
                                        <div className="flex shrink-0 gap-2">
                                            <Button size="icon" variant="outline" asChild>
                                                <Link
                                                    href={`/admin/skills/${skill.id}/edit`}
                                                    aria-label="Modifier la compétence"
                                                    title="Modifier"
                                                >
                                                    <Pencil />
                                                </Link>
                                            </Button>
                                            <Form
                                                action={`/admin/skills/${skill.id}/suspension`}
                                                method="patch"
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        size="icon"
                                                        variant="outline"
                                                        disabled={processing}
                                                        aria-label={
                                                            skill.is_open
                                                                ? 'Suspendre la compétence'
                                                                : 'Lever la suspension'
                                                        }
                                                        title={
                                                            skill.is_open
                                                                ? 'Suspendre'
                                                                : 'Lever la suspension'
                                                        }
                                                    >
                                                        {skill.is_open ? (
                                                            <Ban />
                                                        ) : (
                                                            <RotateCcw />
                                                        )}
                                                    </Button>
                                                )}
                                            </Form>
                                            <Dialog>
                                                <DialogTrigger asChild>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="outline"
                                                        disabled={blocked}
                                                        aria-label="Supprimer la compétence"
                                                        title={
                                                            blocked
                                                                ? 'Cette compétence est utilisée'
                                                                : 'Supprimer'
                                                        }
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>
                                                            Supprimer {skill.name} ?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            La compétence est retirée de sa catégorie.
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <DialogFooter>
                                                        <DialogClose asChild>
                                                            <Button variant="outline" type="button">
                                                                Annuler
                                                            </Button>
                                                        </DialogClose>
                                                        <Form
                                                            action={`/admin/skills/${skill.id}`}
                                                            method="delete"
                                                        >
                                                            {({ processing }) => (
                                                                <Button
                                                                    type="submit"
                                                                    variant="destructive"
                                                                    disabled={processing}
                                                                >
                                                                    Supprimer
                                                                </Button>
                                                            )}
                                                        </Form>
                                                    </DialogFooter>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                )}
                <Pagination page={skills} />
            </PageFrame>
        </>
    );
}

AdminSkillsIndex.layout = {
    breadcrumbs: [{ title: 'Compétences', href: '/admin/skills' }],
};
