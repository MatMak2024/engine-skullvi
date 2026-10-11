import { Form, Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { RotateCcw } from 'lucide-react';
import { PageFrame } from '@/components/talent/page-frame';
import { Pagination } from '@/components/talent/pagination';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/talent';
import type { Page } from '@/types';

type ExcludedAccount = {
    id: string;
    name: string;
    email: string;
    reason: string | null;
    at: string | null;
};

export default function ExcludedCandidates({
    suspended,
    removed,
    canManage = false,
}: {
    suspended: Page<ExcludedAccount>;
    removed: Page<ExcludedAccount>;
    canManage?: boolean;
}) {
    return (
        <>
            <Head title="Comptes écartés" />
            <PageFrame
                eyebrow="Recrutement"
                title="Comptes écartés"
                description="Un compte suspendu peut revenir candidat une fois la situation résolue. Un compte retiré reste utilisateur, mais ne pourra plus jamais être candidat."
            >
                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold text-card-foreground">
                        Suspendus
                    </h2>
                    <AccountList
                        page={suspended}
                        pageName="suspended_page"
                        empty="Aucun candidat suspendu."
                        action={
                            canManage
                                ? (account) => (
                                      <Form
                                          action={`/admin/candidates/${account.id}/suspension`}
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
                                  )
                                : undefined
                        }
                    />
                </section>
                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold text-card-foreground">
                        Retirés
                    </h2>
                    <AccountList
                        page={removed}
                        pageName="removed_page"
                        empty="Aucun candidat retiré."
                    />
                </section>
            </PageFrame>
        </>
    );
}

function AccountList({
    page,
    pageName,
    empty,
    action,
}: {
    page: Page<ExcludedAccount>;
    pageName: string;
    empty: string;
    action?: (account: ExcludedAccount) => ReactNode;
}) {
    if (page.total === 0) {
        return <p className="text-sm text-card-foreground">{empty}</p>;
    }

    return (
        <>
            <ul className="divide-y overflow-hidden rounded-3xl border bg-card text-card-foreground">
                {page.data.map((account) => (
                    <li
                        key={account.id}
                        className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div className="min-w-0">
                            <p className="font-medium">{account.name}</p>
                            <p className="truncate text-sm">
                                {account.email}
                                {' · '}
                                {formatDate(account.at)}
                            </p>
                            <p className="text-sm">
                                {account.reason ?? 'Aucun motif indiqué.'}
                            </p>
                        </div>
                        {action?.(account)}
                    </li>
                ))}
            </ul>
            <Pagination page={page} pageName={pageName} />
        </>
    );
}

ExcludedCandidates.layout = {
    breadcrumbs: [
        { title: 'Candidats', href: '/admin/candidates' },
        { title: 'Écartés', href: '/admin/candidates/excluded' },
    ],
};
