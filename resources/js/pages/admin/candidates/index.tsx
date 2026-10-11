import { Form, Head, Link } from '@inertiajs/react';
import { Ban, RotateCcw, Trash2 } from 'lucide-react';
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
import { textareaClassName } from '@/lib/talent';
import type { Page } from '@/types';

type CandidateRow = {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    country: string;
    city: string | null;
    applications_count: number;
    suspended: boolean;
};

export default function AdminCandidatesIndex({
    candidates,
    canManage = false,
}: {
    candidates: Page<CandidateRow>;
    canManage?: boolean;
}) {
    return (
        <>
            <Head title="Candidats" />
            <PageFrame
                eyebrow="Recrutement"
                title="Candidats"
                description="Suspendre ou retirer un candidat laisse le compte utilisateur. Un candidat retiré ne pourra plus jamais postuler."
            >
                <div className="flex justify-end">
                    <Button variant="outline" asChild>
                        <Link href="/admin/candidates/excluded">
                            Comptes écartés
                        </Link>
                    </Button>
                </div>
                {candidates.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Aucun candidat pour le moment.
                    </p>
                ) : (
                    <ul className="divide-y overflow-hidden rounded-3xl border bg-card">
                        {candidates.data.map((candidate) => {
                            const place = [candidate.city, candidate.country]
                                .filter(Boolean)
                                .join(', ');
                            const details = [
                                candidate.email,
                                candidate.phone,
                                place,
                                `${candidate.applications_count} candidature${candidate.applications_count > 1 ? 's' : ''}`,
                                candidate.suspended ? 'Suspendu' : null,
                            ].filter(Boolean);

                            return (
                                <li
                                    key={candidate.id}
                                    className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium text-foreground">
                                            {candidate.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {details.join(' · ')}
                                        </p>
                                    </div>
                                    {canManage && (
                                        <div className="flex shrink-0 gap-2">
                                            {candidate.suspended ? (
                                                <Form
                                                    action={`/admin/candidates/${candidate.id}/suspension`}
                                                    method="patch"
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            size="icon"
                                                            variant="outline"
                                                            disabled={processing}
                                                            aria-label="Lever la suspension"
                                                            title="Lever la suspension"
                                                        >
                                                            <RotateCcw />
                                                        </Button>
                                                    )}
                                                </Form>
                                            ) : (
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="outline"
                                                            aria-label="Suspendre le candidat"
                                                            title="Suspendre"
                                                        >
                                                            <Ban />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>
                                                                Suspendre {candidate.name} ?
                                                            </DialogTitle>
                                                            <DialogDescription>
                                                                Le compte utilisateur reste. Le candidat est notifié et doit résoudre la situation indiquée.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <Form
                                                            action={`/admin/candidates/${candidate.id}/suspension`}
                                                            method="patch"
                                                            className="flex flex-col gap-4"
                                                        >
                                                            {({ processing, errors }) => (
                                                                <>
                                                                    <label className="flex flex-col gap-2 text-sm" htmlFor={`reason-${candidate.id}`}>
                                                                        Situation à résoudre
                                                                        <textarea
                                                                            id={`reason-${candidate.id}`}
                                                                            name="reason"
                                                                            required
                                                                            maxLength={1000}
                                                                            className={textareaClassName}
                                                                        />
                                                                    </label>
                                                                    {errors.reason && (
                                                                        <p className="text-sm text-[#c56b64]">
                                                                            {errors.reason}
                                                                        </p>
                                                                    )}
                                                                    <DialogFooter>
                                                                        <DialogClose asChild>
                                                                            <Button variant="outline" type="button">
                                                                                Annuler
                                                                            </Button>
                                                                        </DialogClose>
                                                                        <Button type="submit" disabled={processing}>
                                                                            Suspendre
                                                                        </Button>
                                                                    </DialogFooter>
                                                                </>
                                                            )}
                                                        </Form>
                                                    </DialogContent>
                                                </Dialog>
                                            )}
                                            <Dialog>
                                                <DialogTrigger asChild>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="outline"
                                                        aria-label="Supprimer le candidat"
                                                        title="Supprimer"
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>
                                                            Supprimer {candidate.name} ?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Ses dossiers sont retirés. Le compte reste un utilisateur, mais il ne pourra plus jamais être candidat.
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <Form
                                                        action={`/admin/candidates/${candidate.id}`}
                                                        method="delete"
                                                        className="flex flex-col gap-4"
                                                    >
                                                        {({ processing, errors }) => (
                                                            <>
                                                                <label className="flex flex-col gap-2 text-sm" htmlFor={`removal-${candidate.id}`}>
                                                                    Motif du retrait
                                                                    <textarea
                                                                        id={`removal-${candidate.id}`}
                                                                        name="reason"
                                                                        required
                                                                        maxLength={1000}
                                                                        className={textareaClassName}
                                                                    />
                                                                </label>
                                                                {errors.reason && (
                                                                    <p className="text-sm text-[#c56b64]">
                                                                        {errors.reason}
                                                                    </p>
                                                                )}
                                                                <DialogFooter>
                                                                    <DialogClose asChild>
                                                                        <Button variant="outline" type="button">
                                                                            Annuler
                                                                        </Button>
                                                                    </DialogClose>
                                                                    <Button
                                                                        type="submit"
                                                                        variant="destructive"
                                                                        disabled={processing}
                                                                    >
                                                                        Retirer
                                                                    </Button>
                                                                </DialogFooter>
                                                            </>
                                                        )}
                                                    </Form>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                )}
                <Pagination page={candidates} />
            </PageFrame>
        </>
    );
}

AdminCandidatesIndex.layout = {
    breadcrumbs: [{ title: 'Candidats', href: '/admin/candidates' }],
};
