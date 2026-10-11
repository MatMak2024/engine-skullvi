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

type ProgramRow = {
    id: string;
    name: string;
    description: string | null;
    is_open: boolean;
    applications_count: number;
    courses_count: number;
    skills: { id: string; name: string; is_required: boolean }[];
};

export default function AdminProgramsIndex({
    programs,
    canManage = false,
}: {
    programs: Page<ProgramRow>;
    canManage?: boolean;
}) {
    const { errors } = usePage().props;

    return (
        <>
            <Head title="Programmes" />
            <PageFrame
                eyebrow="Recrutement"
                title="Programmes"
                description="Créez un programme, suspendez-le pour fermer les candidatures, ou retirez-le s’il n’a ni cours ni dossier."
                actions={
                    canManage ? (
                        <Button asChild>
                            <Link href="/admin/programs/create">
                                Nouveau programme
                            </Link>
                        </Button>
                    ) : null
                }
            >
                <InputError message={errors.program} />
                {canManage && (
                    <div className="flex justify-end">
                        <Button size="icon" variant="outline" asChild>
                            <Link
                                href="/admin/programs/create"
                                aria-label="Créer un programme"
                                title="Créer un programme"
                            >
                                <Plus />
                            </Link>
                        </Button>
                    </div>
                )}
                {programs.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Aucun programme pour le moment.
                    </p>
                ) : (
                    <ul className="divide-y overflow-hidden rounded-3xl border bg-card">
                        {programs.data.map((program) => {
                            const blocked =
                                program.applications_count > 0 ||
                                program.courses_count > 0;
                            const details = [
                                `${program.courses_count} cours`,
                                `${program.skills.length} compétence${program.skills.length > 1 ? 's' : ''}`,
                                `${program.applications_count} candidature${program.applications_count > 1 ? 's' : ''}`,
                                program.is_open ? null : 'Suspendu',
                            ].filter(Boolean);

                            return (
                                <li
                                    key={program.id}
                                    className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium text-foreground">
                                            {program.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {details.join(' · ')}
                                        </p>
                                    </div>
                                    {canManage && (
                                        <div className="flex shrink-0 gap-2">
                                            <Button size="icon" variant="outline" asChild>
                                                <Link
                                                    href={`/admin/programs/${program.id}/edit`}
                                                    aria-label="Modifier le programme"
                                                    title="Modifier"
                                                >
                                                    <Pencil />
                                                </Link>
                                            </Button>
                                            <Form
                                                action={`/admin/programs/${program.id}/suspension`}
                                                method="patch"
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        size="icon"
                                                        variant="outline"
                                                        disabled={processing}
                                                        aria-label={
                                                            program.is_open
                                                                ? 'Suspendre le programme'
                                                                : 'Lever la suspension'
                                                        }
                                                        title={
                                                            program.is_open
                                                                ? 'Suspendre'
                                                                : 'Lever la suspension'
                                                        }
                                                    >
                                                        {program.is_open ? (
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
                                                        aria-label="Supprimer le programme"
                                                        title={
                                                            blocked
                                                                ? 'Ce programme a des cours ou des candidatures'
                                                                : 'Supprimer'
                                                        }
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>
                                                            Supprimer {program.name} ?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Le programme et ses compétences sont retirés.
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <DialogFooter>
                                                        <DialogClose asChild>
                                                            <Button variant="outline" type="button">
                                                                Annuler
                                                            </Button>
                                                        </DialogClose>
                                                        <Form
                                                            action={`/admin/programs/${program.id}`}
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
                <Pagination page={programs} />
            </PageFrame>
        </>
    );
}

AdminProgramsIndex.layout = {
    breadcrumbs: [{ title: 'Programmes', href: '/admin/programs' }],
};
