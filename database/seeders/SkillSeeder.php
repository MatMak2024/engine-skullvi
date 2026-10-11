<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $college = $this->category('Collège', 'college');
        $lycee = $this->category('Lycée', 'lycee');
        $formations = $this->category('Formations', 'formations');

        $names = [
            ...$this->skills($college, [
                'Les êtres vivants et leur environnement',
                'L\'Homme et sa santé',
                'La nutrition et l\'alimentation des êtres vivants',
                'La reproduction des êtres vivants et l\'hérédité',
                'La géodynamique et la géologie du Togo',
            ]),
            ...$this->skills($lycee, [
                'Les notions de migration et de mutation sociale',
                'Les causes, opportunités et défis des migrations',
                'Migrations et transformations sociales',
                'Mouvement et forces',
                'Systèmes d\'énergie et d\'alimentation',
                'Lumière, son et ondes',
                'Le domaine atomique et nucléaire',
            ]),
            ...$this->skills($formations, [
                'La logique de la machine',
                'Ingénierie avancée des prompts',
                'Flux de travail et automatisation basés sur l\'IA',
                'Éthique et innovation',
                'Fondements numériques et logique Web',
                'Conception centrée sur l\'utilisateur',
                'Maîtrise des données et le cloud',
                'IA et la frontière de l\'automatisation',
                'Identité de marque et stratégie de contenu',
                'Les réseaux sociaux et l\'algorithme',
                'Publicités payantes et marketing à la performance',
                'Analyse et optimisation de la conversion',
                'Maîtrise des cellules',
                'Formules et fonctions logiques',
                'Visualisation des données et graphiques',
                'Tableaux croisés dynamiques et automatisation',
            ]),
        ];

        Skill::query()
            ->whereNotIn('name', $names)
            ->delete();

        Category::query()
            ->whereNotIn('slug', ['college', 'lycee', 'formations'])
            ->delete();
    }

    private function category(string $name, string $slug): Category
    {
        return Category::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $name],
        );
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function skills(Category $category, array $names): array
    {
        foreach ($names as $name) {
            $skill = Skill::query()->firstOrCreate(['name' => $name]);
            $skill->categories()->sync([$category->id]);
        }

        return $names;
    }
}
