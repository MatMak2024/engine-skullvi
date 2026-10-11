import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Sparkles, Star } from 'lucide-react';
import { DemarcheSection } from '@/components/demarche-section';
import { PageEnter } from '@/components/page-enter';
import { SiteFooter } from '@/components/site-footer';
import { SiteNav } from '@/components/site-nav';
import { register } from '@/routes';

const pillars = [
    {
        title: 'Communauté et mentors',
        text: 'Des professionnels et une communauté répondent aux questions des apprenants.',
    },
    {
        title: 'Accompagnement',
        text: 'Des explications, des résumés et un parcours d’étude adapté à chacun.',
    },
    {
        title: 'Révision vivante',
        text: 'Des quiz, une progression et des repères pour avancer sans décrocher.',
    },
    {
        title: 'Cours',
        text: 'Des parcours du secondaire, du collège et des formations libres, dont des programmes du Togo.',
    },
    {
        title: 'Espace tuteurs',
        text: 'Un lieu pour celles et ceux qui enseignent et accompagnent les apprenants.',
    },
    {
        title: 'Forum de jeux',
        text: 'Partager, apprendre et avancer ensemble, avec une progression qui donne envie de revenir.',
    },
];

const contacts = [
    {
        title: 'Belgique',
        lines: ['Rue des Six Jetons 4', '7850 Enghien'],
        href: 'tel:+32470341910',
        label: '+32 470 34 19 10',
    },
    {
        title: 'Togo',
        lines: ['CNSS Baguida SE-2 n° 41', '07 BP 13 793, Lomé'],
        href: 'tel:+22896715334',
        label: '+228 96 71 53 34',
    },
    {
        title: 'Écrire',
        lines: ['Une question, une inscription,', 'ou une collaboration.'],
        href: 'mailto:info@skullvi.org',
        label: 'info@skullvi.org',
    },
];

const limits = [
    'Aucune donnée sensible — genre, âge ou origine — n’entre dans le modèle ni dans le score.',
    'Les preuves comptent si le lien a un format valide. Le système ne juge pas la qualité du contenu.',
    'Un programme fermé rend le dossier non éligible. Un dossier incomplet reste incomplet, à part de l’adéquation.',
    'En cas d’égalité, les preuves vérifiables passent avant, puis la date de candidature.',
];

export default function About() {
    const { auth, name, talent } = usePage().props;

    return (
        <>
            <Head title="À propos" />
            <div className="min-h-screen bg-background text-foreground">
                <SiteNav />
                <PageEnter>
                <main className="flex w-full flex-col">
                    <section className="talent-hero flex min-h-0 items-center overflow-hidden py-12 text-white sm:min-h-[70vh] md:min-h-[92vh] md:px-12 md:py-24">
                        <div className="grid w-full items-stretch gap-10 lg:grid-cols-2 lg:gap-16">
                            <div className="relative order-2 mx-auto w-full max-w-lg px-6 lg:px-4">
                                <Star className="absolute top-0 left-8 size-6 fill-[#b7c48a] text-[#b7c48a]" />
                                <Sparkles className="absolute top-6 right-6 size-7 text-white" />
                                <Star className="absolute bottom-8 left-4 size-4 rotate-12 fill-white text-white" />
                                <Sparkles className="absolute right-8 bottom-2 size-5 text-[#b7c48a]" />
                                <span className="absolute top-1/2 left-1 size-2 rounded-full bg-[#b7c48a]" />
                                <span className="absolute top-16 right-3 size-1.5 rounded-full bg-card" />
                                <div className="grid grid-cols-2 gap-8 lg:gap-12">
                                    {['/images/im1.jpg', '/images/im2.jpg'].map(
                                        (src) => (
                                            <div
                                                key={src}
                                                className="rounded-full bg-[#b7c48a] p-1"
                                            >
                                                <div className="overflow-hidden rounded-full border-[3px] border-white">
                                                    <img
                                                        src={src}
                                                        alt=""
                                                        className="h-40 w-full object-cover sm:h-48 lg:h-56"
                                                    />
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                                <div className="mx-auto mt-10 w-3/5">
                                    <div className="rounded-full bg-[#b7c48a] p-1">
                                        <div className="overflow-hidden rounded-full border-[3px] border-white">
                                            <img
                                                src="/images/herim.jpg"
                                                alt=""
                                                className="h-44 w-full object-cover sm:h-52 lg:h-60"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="order-1 flex max-w-2xl flex-col gap-6 px-6 py-6 md:min-h-[68vh] md:justify-between md:py-10">
                                <p className="text-sm font-medium tracking-wide text-[#b7c48a] uppercase">
                                    À propos
                                </p>
                                <h1 className="text-3xl font-semibold tracking-tight text-balance text-white sm:text-4xl md:text-6xl">
                                    Un outil pour examiner, pas pour décider.
                                </h1>
                                <p className="max-w-xl text-base leading-8 text-white/80 md:text-lg">
                                    {name} aide à recevoir les candidatures,
                                    qualifier les profils, attribuer un score et
                                    identifier ceux qu’un humain doit regarder
                                    en priorité.
                                </p>
                                <div>
                                    {auth.user ? (
                                        <Link
                                            href={
                                                talent.roles.includes('recruiter') &&
                                                !talent.roles.includes('super_admin')
                                                    ? '/admin/applications'
                                                    : '/courses'
                                            }
                                            className="inline-flex items-center gap-2 rounded-full bg-[#b7c48a] px-6 py-3 text-sm font-medium text-[#023940]"
                                        >
                                            {talent.roles.includes('recruiter') &&
                                            !talent.roles.includes('super_admin')
                                                ? 'Candidatures'
                                                : 'Voir les cours'}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    ) : (
                                        <Link
                                            href={register()}
                                            className="inline-flex items-center gap-2 rounded-full bg-[#b7c48a] px-6 py-3 text-sm font-medium text-[#023940]"
                                        >
                                            Créer un compte
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>

                    <DemarcheSection />

                    <section className="talent-hero overflow-hidden px-6 py-16 text-white md:px-16">
                        <h2 className="text-3xl font-semibold tracking-tight">
                            SKULLVI
                        </h2>
                        <p className="mt-3 max-w-6xl text-lg text-white/80 md:text-xl">
                            SKULLVI est une plateforme d’apprentissage à haute
                            efficacité, pensée pour l’ère numérique. On y
                            révise, on joue et on progresse : quiz, rythme
                            personnel, compétences pour l’école et pour le
                            métier. Les enseignants y ont leur place. Le mot
                            d’ordre : planifie ta réussite en un clic.
                            Transforme ton savoir en succès.
                        </p>
                        <div className="mt-8 grid gap-3 text-center sm:grid-cols-2 lg:grid-cols-3">
                            {pillars.map((pillar) => (
                                <div
                                    key={pillar.title}
                                    className="rounded-2xl bg-white/10 px-5 py-8"
                                >
                                    <p className="text-lg font-semibold text-[#b7c48a]">
                                        {pillar.title}
                                    </p>
                                    <p className="mt-3 text-sm leading-6 text-white/80">
                                        {pillar.text}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="px-6 py-16 md:px-16">
                        <h2 className="text-3xl font-semibold tracking-tight">
                            Contacts
                        </h2>
                        <p className="mt-3 max-w-3xl text-base leading-7 text-[#3d5c62]">
                            SKULLVI accueille sa communauté depuis la Belgique
                            et le Togo. La plateforme est en ligne sur{' '}
                            <a
                                href="https://www.skullvi.org/"
                                target="_blank"
                                rel="noreferrer"
                                className="font-medium text-[#023940] underline"
                            >
                                skullvi.org
                            </a>
                            .
                        </p>
                        <div className="mt-8 grid gap-4 md:grid-cols-3">
                            {contacts.map((contact) => (
                                <article
                                    key={contact.title}
                                    className="rounded-3xl bg-card p-6 text-center shadow-sm"
                                >
                                    <h3 className="text-xl font-semibold text-[#023940]">
                                        {contact.title}
                                    </h3>
                                    {contact.lines.map((line) => (
                                        <p
                                            key={line}
                                            className="mt-2 text-sm leading-6 text-[#3d5c62]"
                                        >
                                            {line}
                                        </p>
                                    ))}
                                    <a
                                        href={contact.href}
                                        className="mt-4 inline-flex rounded-full bg-[#b7c48a] px-5 py-2 text-sm font-medium text-[#023940]"
                                    >
                                        {contact.label}
                                    </a>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="mx-6 mb-16 rounded-3xl bg-card p-6 shadow-sm md:mx-16 md:p-10">
                        <h2 className="text-2xl font-semibold">
                            Ce que le score ne fait pas
                        </h2>
                        <ul className="mt-6 space-y-4">
                            {limits.map((limit) => (
                                <li
                                    key={limit}
                                    className="border-l-4 border-[#b7c48a] pl-4 text-sm leading-6 text-[#3d5c62]"
                                >
                                    {limit}
                                </li>
                            ))}
                        </ul>
                    </section>
                </main>
                <SiteFooter />
                </PageEnter>
            </div>
        </>
    );
}
