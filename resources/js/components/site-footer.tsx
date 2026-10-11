import { Link, usePage } from '@inertiajs/react';
import { StableName } from '@/components/stable-name';
import { login, register } from '@/routes';

const explore = [
    { title: 'Accueil', href: '/#accueil' },
    { title: 'Cours', href: '/courses' },
    { title: 'Programmes', href: '/programs' },
    { title: 'Offres', href: '/offers' },
    { title: 'FAQ', href: '/#faq' },
    { title: 'À propos', href: '/a-propos' },
];

export function SiteFooter() {
    const { auth, name } = usePage().props;
    const dashboardUrl = auth.user ? '/dashboard' : login();
    const year = new Date().getFullYear();

    const access = auth.user
        ? [
              { title: 'Tableau de bord', href: dashboardUrl },
              { title: 'Cours', href: '/courses' },
          ]
        : [
              { title: 'Tableau de bord', href: login() },
              { title: 'Connexion', href: login() },
              { title: 'Créer un compte', href: register() },
          ];

    return (
        <footer className="mt-8 bg-[#023940] text-white">
            <div className=" grid w-full gap-10 px-6 py-12 md:grid-cols-[1.4fr_1fr_1fr] md:px-16">
                <div>
                    <p className="text-xl text-[#b7c48a] font-semibold">
                        <StableName>{name}</StableName>
                    </p>
                    <p className="mt-3 max-w-sm text-lg leading-6 text-white/75">
                        Recevoir les candidatures, les classer et indiquer
                        lesquelles examiner en premier. La décision finale
                        reste humaine.
                    </p>
                </div>
                <div>
                    <p className="text-lg font-medium tracking-wide text-[#b7c48a] uppercase">
                        Explorer
                    </p>
                    <ul className="mt-4 space-y-2 text-base">
                        {explore.map((item) => (
                            <li key={item.title}>
                                <a
                                    href={item.href}
                                    className="text-white/80 hover:text-white"
                                >
                                    <StableName>{item.title}</StableName>
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <p className="text-lg font-medium tracking-wide text-[#b7c48a] uppercase">
                        Accès
                    </p>
                    <ul className="mt-4 space-y-2 text-base">
                        {access.map((item) => (
                            <li key={item.title}>
                                <Link
                                    href={item.href}
                                    className="text-white/80 hover:text-white"
                                >
                                    <StableName>{item.title}</StableName>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
            <div className="border-t border-white/10">
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-2 px-6 py-5 text-sm text-white/60 md:flex-row md:items-center md:justify-between md:px-8">
                    <p>
                        © {year} {name} by{' '}
                        <a
                            href="https://alliancematondo.vercel.app"
                            target="_blank"
                            rel="noreferrer"
                            className="text-[#b7c48a] hover:underline"
                        >
                            Alliance Matondo
                        </a>
                    </p>
                    <p>Le score aide à prioriser. Il ne décide pas.</p>
                </div>
            </div>
        </footer>
    );
}
