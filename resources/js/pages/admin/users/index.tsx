import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageFrame } from '@/components/talent/page-frame';
import { Pagination } from '@/components/talent/pagination';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import type { Page } from '@/types';

type Account = {
    id: string;
    name: string;
    email: string;
    roles: string[];
};

type RoleOption = {
    value: string;
    label: string;
};

export default function AdminUsersIndex({
    accounts,
    roleOptions,
}: {
    accounts: Page<Account>;
    roleOptions: RoleOption[];
}) {
    return (
        <>
            <Head title="Utilisateurs" />
            <PageFrame
                eyebrow="Administration"
                title="Utilisateurs"
                description="Attribuez les rôles. Un nouveau compte est un utilisateur simple. Il devient candidat lorsqu’il postule."
            >
                <div className="grid gap-4">
                    {accounts.data.map((account) => (
                        <Card key={account.id}>
                            <CardHeader>
                                <CardTitle>{account.name}</CardTitle>
                                <CardDescription>{account.email}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    action={`/admin/users/${account.id}`}
                                    method="put"
                                    className="grid gap-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="flex flex-wrap gap-4">
                                                {roleOptions.map((role) => (
                                                    <label
                                                        key={role.value}
                                                        className="flex items-center gap-2 text-sm"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            name="roles[]"
                                                            value={role.value}
                                                            defaultChecked={account.roles.includes(role.value)}
                                                            className="size-4"
                                                        />
                                                        {role.label}
                                                    </label>
                                                ))}
                                            </div>
                                            <InputError message={errors.roles} />
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="w-fit"
                                            >
                                                {processing && <Spinner />}
                                                Enregistrer les rôles
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    ))}
                </div>
                <Pagination page={accounts} />
            </PageFrame>
        </>
    );
}

AdminUsersIndex.layout = {
    breadcrumbs: [{ title: 'Utilisateurs', href: '/admin/users' }],
};
