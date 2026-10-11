import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Sparkles, Star } from 'lucide-react';
import { DemarcheSection } from '@/components/demarche-section';
import { PageEnter } from '@/components/page-enter';
import { SiteFooter } from '@/components/site-footer';
import { SiteNav } from '@/components/site-nav';

const questions = [
    {
        question: 'Le score décide-t-il à la place du recruteur ?',
        answer: 'Non. Il classe les dossiers et propose un statut. Le statut affiché sur la candidature change seulement quand un humain le modifie.',
    },
    {
        question: 'À partir de quel score un dossier est-il prioritaire ?',
        answer: '70 ou plus : prioritaire. De 50 à 69 : à examiner. En dessous de 50 : adéquation faible. Ces seuils indiquent une priorité, ils ne tranchent pas.',
    },
    {
        question: 'Quand un compte devient-il un candidat ?',
        answer: 'Au moment où la personne postule. Avant cela, elle reste un utilisateur. Il n’existe pas de rôle candidat.',
    },
    {
        question: 'Que mesurent les preuves ?',
        answer: 'Un GitHub, un portfolio ou un LinkedIn comptent si le lien a un format valide. Le système ne juge pas la qualité du contenu.',
    },
    {
        question: 'Des informations sensibles entrent-elles dans le score ?',
        answer: 'Non. Le genre, l’âge et l’origine ne font partie ni du dossier ni du calcul. Le pays et la ville servent seulement à rapprocher le candidat du lieu de l’offre : télétravail, sur place ou hybride.',
    },
];

const criteria = [
    ['Compétences', '30'],
    ['Expérience', '20'],
    ['Formation', '15'],
    ['Preuves', '15'],
    ['Motivation', '10'],
    ['Disponibilité', '5'],
    ['Localisation', '5'],
];

export default function Welcome() {
    const { auth, name, talent } = usePage().props;

    return (
        <>
            <Head title="Accueil" />
            <div className="min-h-screen bg-background text-foreground">
                <SiteNav />
                <PageEnter>
                <main className="flex w-full flex-col">
                    <section
                        id="accueil"
                        className="talent-hero flex min-h-0 scroll-mt-24 items-center overflow-hidden py-12 text-white sm:min-h-[70vh] md:min-h-[92vh] md:px-12 md:py-24"
                    >
                        <div className="grid w-full items-stretch gap-16 lg:grid-cols-2">
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
                                    Candidatures
                                </p>
                                <h1 className="text-3xl font-semibold tracking-tight text-balance text-white sm:text-4xl md:text-6xl">
                                    Repérez les meilleurs talents en quelques
                                    secondes.
                                </h1>
                                <p className="max-w-xl text-base leading-7 text-white/80 sm:leading-8 md:text-lg">
                                    {name} centralise vos candidatures, évalue
                                    chaque profil selon une grille de scoring
                                    précise et classe automatiquement les
                                    candidats. Plus de tri manuel : les profils
                                    les plus prometteurs remontent
                                    d'eux-mêmes.
                                </p>
                                <div>
                                    <Link
                                        href={
                                            auth.user &&
                                            talent.roles.includes('recruiter') &&
                                            !talent.roles.includes('super_admin')
                                                ? '/admin/applications'
                                                : '/courses'
                                        }
                                        className="inline-flex items-center gap-2 rounded-full bg-[#b7c48a] px-6 py-3 text-sm font-medium text-[#023940]"
                                    >
                                        {auth.user &&
                                        talent.roles.includes('recruiter') &&
                                        !talent.roles.includes('super_admin')
                                            ? 'Candidatures'
                                            : 'Voir les cours'}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </section>

                    <DemarcheSection />

                    <section
                        id="score"
                        className="talent-hero scroll-mt-24 overflow-hidden py-16 text-white px-6 md:px-16"
                    >
                        <h2 className="text-3xl font-semibold tracking-tight">
                            Un score sur 100
                        </h2>
                        <p className="mt-3 max-w-6xl text-lg text-white/80 md:text-xl">
                            Sept critères produisent une indication. Au-dessus de
                            70, le dossier est prioritaire. Entre 50 et 69, il
                            est à examiner. En dessous, l’adéquation est faible.
                            Le statut du dossier, lui, reste une décision
                            humaine.
                        </p>
                        <div className="mt-8 grid text-center gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {criteria.map(([label, points]) => (
                                <div
                                    key={label}
                                    className="rounded-2xl bg-white/10 px-5 py-8"
                                >
                                    <p className="text-lg text-white/75">
                                        {label}
                                    </p>
                                    <p className="mt-1 text-3xl font-serif font-semibold text-[#b7c48a]">
                                        {points}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section id="faq" className="mx-auto max-w-5xl scroll-mt-24 px-6 py-16 md:px-16">
                        <h2 className="text-3xl text-center font-semibold tracking-tight">
                            Questions fréquentes
                        </h2>
                        <p className="mt-3 max-w-5xl text-sm text-[#3d5c62] text-center md:text-base">
                            Ce que le système fait, et ce qu’il laisse à la
                            personne qui examine.
                        </p>
                        <div className="mt-6 flex flex-col gap-3">
                            {questions.map((item) => (
                                <details
                                    key={item.question}
                                    className="group rounded-3xl text-center bg-card px-6 py-4 shadow-sm"
                                >
                                    <summary className="cursor-pointer list-none text-base font-medium">
                                        {item.question}
                                    </summary>
                                    <p className="mt-3 text-sm leading-6 text-[#3d5c62]">
                                        {item.answer}
                                    </p>
                                </details>
                            ))}
                        </div>
                    </section>
                </main>
                <SiteFooter />
                </PageEnter>
            </div>
        </>
    );
}
