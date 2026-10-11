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
import { workPlaceLabel } from '@/lib/talent';
import type { Page, WorkMode } from '@/types';

type OfferRow = {
    id: string;
    name: string;
    is_open: boolean;
    work_mode: WorkMode;
    work_city: string | null;
    work_country: string | null;
    applications_count: number;
};

export default function AdminOffersIndex({
    offers,
    canManage = false,
}: {
    offers: Page<OfferRow>;
    canManage?: boolean;
}) {
    const { errors } = usePage().props;

    return (
        <>
            <Head title="Offres" />
            <PageFrame
                eyebrow="Recrutement"
                title="Offres"
                description="Créez une offre, suspendez-la pour fermer les candidatures, ou retirez-la si elle n’a aucun dossier."
                actions={
                    canManage ? (
                        <Button asChild>
                            <Link href="/admin/offers/create">Nouvelle offre</Link>
                        </Button>
                    ) : null
                }
            >
                <InputError message={errors.offer} />
                {canManage && (
                    <div className="flex justify-end">
                        <Button size="icon" variant="outline" asChild>
                            <Link
                                href="/admin/offers/create"
                                aria-label="Créer une offre"
                                title="Créer une offre"
                            >
                                <Plus />
                            </Link>
                        </Button>
                    </div>
                )}
                {offers.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Aucune offre pour le moment.
                    </p>
                ) : (
                    <ul className="divide-y overflow-hidden rounded-3xl border bg-card">
                        {offers.data.map((offer) => {
                            const details = [
                                workPlaceLabel(
                                    offer.work_mode,
                                    offer.work_city,
                                    offer.work_country,
                                ),
                                `${offer.applications_count} candidature${offer.applications_count > 1 ? 's' : ''}`,
                                offer.is_open ? null : 'Suspendue',
                            ].filter(Boolean);

                            return (
                                <li
                                    key={offer.id}
                                    className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium text-foreground">
                                            {offer.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {details.join(' · ')}
                                        </p>
                                    </div>
                                    {canManage && (
                                        <div className="flex shrink-0 gap-2">
                                            <Button size="icon" variant="outline" asChild>
                                                <Link
                                                    href={`/admin/offers/${offer.id}/edit`}
                                                    aria-label="Modifier l’offre"
                                                    title="Modifier"
                                                >
                                                    <Pencil />
                                                </Link>
                                            </Button>
                                            <Form
                                                action={`/admin/offers/${offer.id}/suspension`}
                                                method="patch"
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        size="icon"
                                                        variant="outline"
                                                        disabled={processing}
                                                        aria-label={
                                                            offer.is_open
                                                                ? 'Suspendre l’offre'
                                                                : 'Lever la suspension'
                                                        }
                                                        title={
                                                            offer.is_open
                                                                ? 'Suspendre'
                                                                : 'Lever la suspension'
                                                        }
                                                    >
                                                        {offer.is_open ? (
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
                                                        disabled={offer.applications_count > 0}
                                                        aria-label="Supprimer l’offre"
                                                        title={
                                                            offer.applications_count > 0
                                                                ? 'Cette offre a des candidatures'
                                                                : 'Supprimer'
                                                        }
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>
                                                            Supprimer {offer.name} ?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            L’offre et ses compétences sont retirées.
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <DialogFooter>
                                                        <DialogClose asChild>
                                                            <Button variant="outline" type="button">
                                                                Annuler
                                                            </Button>
                                                        </DialogClose>
                                                        <Form
                                                            action={`/admin/offers/${offer.id}`}
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
                <Pagination page={offers} />
            </PageFrame>
        </>
    );
}

AdminOffersIndex.layout = {
    breadcrumbs: [{ title: 'Offres', href: '/admin/offers' }],
};
