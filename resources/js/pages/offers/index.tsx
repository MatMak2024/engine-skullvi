import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PublicPage } from '@/components/public-page';
import { StableName } from '@/components/stable-name';
import { Pagination } from '@/components/talent/pagination';
import { workPlaceLabel } from '@/lib/talent';
import type { Page, WorkMode } from '@/types';

type OfferSummary = {
    id: string;
    name: string;
    description: string | null;
    work_mode: WorkMode;
    work_city: string | null;
    work_country: string | null;
};

export default function OffersIndex({ offers }: { offers: Page<OfferSummary> }) {
    return (
        <PublicPage title="Offres">
            <section className="talent-hero overflow-hidden px-6 py-16 text-white md:px-16">
                <p className="text-sm font-medium tracking-wide text-[#b7c48a] uppercase">
                    Offres
                </p>
                <h1 className="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-6xl">
                    Les offres ouvertes
                </h1>
                <p className="mt-4 max-w-2xl text-base leading-8 text-white/80 md:text-lg">
                    Plusieurs candidats peuvent postuler à la même offre. Le
                    CV et la lettre de motivation sont notés par rapport à
                    cette offre.
                </p>
            </section>

            <section className="px-6 py-16 md:px-16">
                {offers.total === 0 ? (
                    <p className="text-muted-foreground">
                        Aucune offre n'est ouverte pour le moment.
                    </p>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {offers.data.map((offer) => (
                            <article
                                key={offer.id}
                                className="flex flex-col rounded-3xl bg-card p-6 text-center shadow-sm"
                            >
                                <h2 className="text-xl font-semibold text-foreground">
                                    <StableName>{offer.name}</StableName>
                                </h2>
                                <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                    {workPlaceLabel(
                                        offer.work_mode,
                                        offer.work_city,
                                        offer.work_country,
                                    )}
                                </p>
                                {offer.description && (
                                    <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                        {offer.description}
                                    </p>
                                )}
                                <Link
                                    href={`/offers/${offer.id}`}
                                    className="mt-6 inline-flex items-center justify-center gap-2 self-center rounded-full bg-[#b7c48a] px-5 py-2 text-sm font-medium text-foreground"
                                >
                                    Voir l'offre
                                    <ArrowRight className="size-4" />
                                </Link>
                            </article>
                        ))}
                    </div>
                )}
                <div className="mt-8">
                    <Pagination page={offers} />
                </div>
            </section>
        </PublicPage>
    );
}
