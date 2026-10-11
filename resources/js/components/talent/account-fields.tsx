import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/talent';
import type { User } from '@/types';

type AccountErrors = Partial<
    Record<
        | 'first_name'
        | 'name'
        | 'email'
        | 'phone'
        | 'address'
        | 'city'
        | 'state'
        | 'zip'
        | 'country',
        string
    >
>;

function Field({
    id,
    label,
    type = 'text',
    value,
    autoComplete,
    required = false,
    error,
}: {
    id: string;
    label: string;
    type?: string;
    value: string;
    autoComplete?: string;
    required?: boolean;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                name={id}
                type={type}
                defaultValue={value}
                required={required}
                autoComplete={autoComplete}
            />
            <InputError message={error} />
        </div>
    );
}

export function AccountFields({
    user,
    errors,
}: {
    user: User;
    errors: AccountErrors;
}) {
    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field
                    id="first_name"
                    label="Prénom"
                    value={user.first_name ?? ''}
                    autoComplete="given-name"
                    required
                    error={errors.first_name}
                />
                <Field
                    id="name"
                    label="Nom"
                    value={user.name}
                    autoComplete="family-name"
                    required
                    error={errors.name}
                />
            </div>
            <Field
                id="email"
                label="Adresse e-mail"
                type="email"
                value={user.email}
                autoComplete="username"
                required
                error={errors.email}
            />
            <Field
                id="phone"
                label="Téléphone"
                type="tel"
                value={user.phone ?? ''}
                autoComplete="tel"
                error={errors.phone}
            />
            <Field
                id="address"
                label="Adresse"
                value={user.address ?? ''}
                autoComplete="street-address"
                error={errors.address}
            />
            <div className="grid gap-4 sm:grid-cols-2">
                <Field
                    id="zip"
                    label="Code postal"
                    value={user.zip ?? ''}
                    autoComplete="postal-code"
                    error={errors.zip}
                />
                <Field
                    id="city"
                    label="Ville"
                    value={user.city ?? ''}
                    autoComplete="address-level2"
                    error={errors.city}
                />
                <Field
                    id="state"
                    label="Région"
                    value={user.state ?? ''}
                    autoComplete="address-level1"
                    error={errors.state}
                />
                <Field
                    id="country"
                    label="Pays"
                    value={user.country ?? ''}
                    autoComplete="country-name"
                    error={errors.country}
                />
            </div>
            <dl className="text-muted-foreground grid gap-2 text-sm">
                <div className="flex flex-wrap justify-between gap-2">
                    <dt>E-mail vérifié</dt>
                    <dd>
                        {user.email_verified_at
                            ? formatDate(user.email_verified_at)
                            : 'Non vérifié'}
                    </dd>
                </div>
                <div className="flex flex-wrap justify-between gap-2">
                    <dt>Téléphone vérifié</dt>
                    <dd>
                        {user.phone_verified_at
                            ? formatDate(user.phone_verified_at)
                            : 'Non vérifié'}
                    </dd>
                </div>
                <div className="flex flex-wrap justify-between gap-2">
                    <dt>Compte créé</dt>
                    <dd>{formatDate(user.created_at)}</dd>
                </div>
            </dl>
        </>
    );
}
