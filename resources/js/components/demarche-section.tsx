import {
    Flag,
    FolderOpen,
    Gauge,
    ListOrdered,
    ShieldCheck,
    UserPlus,
    type LucideIcon,
} from 'lucide-react';

const principles: {
    title: string;
    text: string;
    icon: LucideIcon;
}[] = [
    {
        title: 'Candidater',
        text: 'Un parcours simple, pensé pour le candidat. Chaque programme ouvert propose son propre formulaire, clair et guidé. Le candidat crée son profil, choisit son programme et soumet sa candidature en quelques minutes, sans pièce manquante ni aller-retour.',
        icon: UserPlus,
    },
    {
        title: 'Collecter',
        text: 'Des dossiers complets, structurés dès le départ.Parcours, compétences et niveaux, expérience, projets, liens GitHub et portfolio, motivation : tout est capturé dans un format unique et exploitable. Fini les CV hétérogènes à décrypter, chaque dossier arrive prêt à être évalué.',
        icon: FolderOpen,
    },
    {
        title: 'Qualifier',
        text: `Les dossiers fiables d'abord, les anomalies écartées. Dès la réception, le système contrôle l'éligibilité, mesure le taux de complétude du dossier et détecte les doublons et les liens invalides. Chaque candidature reçoit un statut clair avant même d'être notée.`,
        icon: ShieldCheck,
    },
    {
        title: 'Scorer',
        text: `Une note sur 100, détaillée critère par critère. Compétences requises et bonus, expérience, formation, preuves concrètes, motivation, disponibilité et localisation : sept critères pondérés produisent un score objectif, appliqué de la même façon à tous les candidats. Le lieu ajoute des points selon que l'offre est en télétravail, sur place ou hybride. Chaque point est expliqué, il n'y a aucune boîte noire.`,
        icon: Gauge,
    },
    {
        title: 'Classer',
        text: `Un classement toujours à jour. À chaque nouvelle candidature, le classement se recalcule instantanément. Filtrez par programme ou par statut, triez par score, et visualisez en un coup d'œil où se situe chaque profil.`,
        icon: ListOrdered,
    },
    {
        title: 'Prioriser',
        text: `Les meilleurs profils en tête, prêts à être examinés. Les candidatures à fort potentiel remontent automatiquement en haut de la liste, avec le détail du score sous les yeux. Votre équipe commence par ce qui compte, change le statut d'un dossier et ajoute ses notes en quelques clics.`,
        icon: Flag,
    },
];

export function DemarcheSection() {
    return (
        <section id="demarche" className="relative scroll-mt-24 overflow-hidden">
            <img
                src="/images/im3.jpg"
                alt=""
                className="pointer-events-none absolute inset-0 h-full w-full object-cover blur-sm"
            />
            <div className="relative grid gap-4 px-6 py-16 md:grid-cols-3 md:px-16">
                {principles.map((principle) => {
                    const Icon = principle.icon;

                    return (
                        <article
                            key={principle.title}
                            className="border-border bg-card text-card-foreground rounded-3xl border p-6 text-center shadow-lg"
                        >
                            <div className="mx-auto flex size-11 items-center justify-center rounded-2xl bg-[#023940] text-[#b7c48a] dark:bg-[#b7c48a] dark:text-[#023940]">
                                <Icon className="size-5" />
                            </div>
                            <h2 className="text-card-foreground mt-4 text-xl font-semibold">
                                {principle.title}
                            </h2>
                            <p className="text-muted-foreground text-md mt-3 leading-6">
                                {principle.text}
                            </p>
                        </article>
                    );
                })}
            </div>
        </section>
    );
}
