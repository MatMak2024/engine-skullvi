import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { PageFrame } from '@/components/talent/page-frame';
import {
    SkillChoices,
    type SkillOption,
} from '@/components/talent/skill-choices';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { fieldClassName, workModeLabels } from '@/lib/talent';
import type { WorkMode } from '@/types';

type OfferFormData = {
    id: string;
    name: string;
    description: string | null;
    is_open: boolean;
    work_mode: WorkMode;
    work_country: string | null;
    work_city: string | null;
    required_skills: string[];
    bonus_skills: string[];
    requested_documents?: string[];
};

export default function AdminOfferForm({
    offer,
    skills,
}: {
    offer: OfferFormData | null;
    skills: SkillOption[];
}) {
    const action = offer ? `/admin/offers/${offer.id}` : '/admin/offers';
    const [workMode, setWorkMode] = useState<WorkMode>(
        offer?.work_mode ?? 'remote',
    );
    const asksForPlace = workMode !== 'remote';

    return (
        <>
            <Head title={offer ? offer.name : 'Nouvelle offre'} />
            <PageFrame
                eyebrow="Recrutement"
                title={offer ? 'Modifier l’offre' : 'Nouvelle offre'}
                description="Les compétences de l’offre servent à noter le CV et la lettre de motivation."
            >
                <Form
                    action={action}
                    method={offer ? 'put' : 'post'}
                    className="grid gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={offer?.name ?? ''}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    defaultValue={offer?.description ?? ''}
                                    rows={4}
                                    className="border-input w-full rounded-xl border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="requested_documents">
                                    Documents de qualification demandés
                                </Label>
                                <textarea
                                    id="requested_documents"
                                    name="requested_documents"
                                    defaultValue={(
                                        offer?.requested_documents ?? []
                                    ).join('\n')}
                                    rows={3}
                                    placeholder="Un document par ligne, par exemple Diplôme"
                                    className="border-input w-full rounded-xl border bg-transparent px-3 py-2 text-sm text-[#0e2a2e] shadow-xs outline-none focus-visible:ring-[3px]"
                                />
                                <InputError message={errors.requested_documents} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="work_mode">Mode de travail</Label>
                                <select
                                    id="work_mode"
                                    name="work_mode"
                                    value={workMode}
                                    onChange={(event) =>
                                        setWorkMode(event.target.value as WorkMode)
                                    }
                                    className={fieldClassName}
                                >
                                    {Object.entries(workModeLabels).map(
                                        ([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </select>
                                <InputError message={errors.work_mode} />
                            </div>
                            {asksForPlace && (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="work_country">
                                            Pays du lieu
                                        </Label>
                                        <Input
                                            id="work_country"
                                            name="work_country"
                                            required={workMode === 'onsite'}
                                            defaultValue={
                                                offer?.work_country ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.work_country}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="work_city">
                                            Ville du lieu
                                        </Label>
                                        <Input
                                            id="work_city"
                                            name="work_city"
                                            required={workMode === 'onsite'}
                                            defaultValue={
                                                offer?.work_city ?? ''
                                            }
                                        />
                                        <InputError message={errors.work_city} />
                                    </div>
                                </div>
                            )}
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="is_open"
                                    value="1"
                                    defaultChecked={offer?.is_open ?? true}
                                    className="size-4"
                                />
                                    Offre ouverte aux candidatures
                            </label>
                            <SkillChoices
                                title="Compétences requises"
                                name="required_skills[]"
                                skills={skills}
                                selected={offer?.required_skills ?? []}
                                error={errors.required_skills}
                            />
                            <SkillChoices
                                title="Compétences bonus"
                                name="bonus_skills[]"
                                skills={skills}
                                selected={offer?.bonus_skills ?? []}
                                error={errors.bonus_skills}
                            />
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Enregistrer
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/offers">Retour</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </PageFrame>
        </>
    );
}

AdminOfferForm.layout = {
    breadcrumbs: [
        { title: 'Offres', href: '/admin/offers' },
        { title: 'Formulaire', href: '/admin/offers/create' },
    ],
};
