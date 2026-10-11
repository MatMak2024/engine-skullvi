import InputError from '@/components/input-error';

export type SkillOption = {
    id: string;
    name: string;
    category: string | null;
};

export function SkillChoices({
    title,
    name,
    skills,
    selected,
    error,
}: {
    title: string;
    name: string;
    skills: SkillOption[];
    selected: string[];
    error?: string;
}) {
    const groups = new Map<string, SkillOption[]>();

    for (const skill of skills) {
        const category = skill.category ?? 'Sans catégorie';
        const items = groups.get(category) ?? [];
        items.push(skill);
        groups.set(category, items);
    }

    const categories = [...groups.keys()].sort((left, right) => {
        if (left === 'Sans catégorie') {
            return 1;
        }

        if (right === 'Sans catégorie') {
            return -1;
        }

        return left.localeCompare(right, 'fr');
    });

    return (
        <fieldset className="grid gap-4">
            <legend className="text-sm font-medium">{title}</legend>
            {skills.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    Aucune compétence n’est encore enregistrée.
                </p>
            ) : (
                categories.map((category) => (
                    <div key={`${name}-${category}`} className="grid gap-2">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {category}
                        </p>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {groups.get(category)?.map((skill) => (
                                <label
                                    key={`${name}-${skill.id}`}
                                    className="flex items-start gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        name={name}
                                        value={skill.id}
                                        defaultChecked={selected.includes(skill.id)}
                                        className="mt-1 size-4"
                                    />
                                    <span>{skill.name}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                ))
            )}
            <InputError message={error} />
        </fieldset>
    );
}
