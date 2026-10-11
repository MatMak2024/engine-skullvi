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

type CourseRow = {
    id: string;
    name: string;
    description: string | null;
    is_open: boolean;
    program_name: string | null;
    applications_count: number;
};

export default function AdminCoursesIndex({
    courses,
    canManage = false,
}: {
    courses: Page<CourseRow>;
    canManage?: boolean;
}) {
    const { errors } = usePage().props;

    return (
        <>
            <Head title="Cours" />
            <PageFrame
                eyebrow="Recrutement"
                title="Cours"
                description="Créez un cours, suspendez-le pour fermer les candidatures, ou retirez-le s’il n’a aucun dossier."
                actions={
                    canManage ? (
                        <Button asChild>
                            <Link href="/admin/courses/create">Nouveau cours</Link>
                        </Button>
                    ) : null
                }
            >
                <InputError message={errors.course} />
                {canManage && (
                    <div className="flex justify-end">
                        <Button size="icon" variant="outline" asChild>
                            <Link
                                href="/admin/courses/create"
                                aria-label="Créer un cours"
                                title="Créer un cours"
                            >
                                <Plus />
                            </Link>
                        </Button>
                    </div>
                )}
                {courses.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Aucun cours pour le moment.
                    </p>
                ) : (
                    <ul className="divide-y overflow-hidden rounded-3xl border bg-card">
                        {courses.data.map((course) => {
                            const details = [
                                course.program_name ?? 'Sans programme',
                                `${course.applications_count} candidature${course.applications_count > 1 ? 's' : ''}`,
                                course.is_open ? null : 'Suspendu',
                            ].filter(Boolean);

                            return (
                                <li
                                    key={course.id}
                                    className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium text-foreground">
                                            {course.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {details.join(' · ')}
                                        </p>
                                    </div>
                                    {canManage && (
                                        <div className="flex shrink-0 gap-2">
                                            <Button
                                                size="icon"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={`/admin/courses/${course.id}/edit`}
                                                    aria-label="Modifier le cours"
                                                    title="Modifier"
                                                >
                                                    <Pencil />
                                                </Link>
                                            </Button>
                                            <Form
                                                action={`/admin/courses/${course.id}/suspension`}
                                                method="patch"
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        size="icon"
                                                        variant="outline"
                                                        disabled={processing}
                                                        aria-label={
                                                            course.is_open
                                                                ? 'Suspendre le cours'
                                                                : 'Lever la suspension'
                                                        }
                                                        title={
                                                            course.is_open
                                                                ? 'Suspendre'
                                                                : 'Lever la suspension'
                                                        }
                                                    >
                                                        {course.is_open ? (
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
                                                        disabled={
                                                            course.applications_count > 0
                                                        }
                                                        aria-label="Supprimer le cours"
                                                        title={
                                                            course.applications_count > 0
                                                                ? 'Ce cours a des candidatures'
                                                                : 'Supprimer'
                                                        }
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>
                                                            Supprimer {course.name} ?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Le cours et ses compétences prérequises sont retirés.
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <DialogFooter>
                                                        <DialogClose asChild>
                                                            <Button variant="outline" type="button">
                                                                Annuler
                                                            </Button>
                                                        </DialogClose>
                                                        <Form
                                                            action={`/admin/courses/${course.id}`}
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
                <Pagination page={courses} />
            </PageFrame>
        </>
    );
}

AdminCoursesIndex.layout = {
    breadcrumbs: [{ title: 'Cours', href: '/admin/courses' }],
};
