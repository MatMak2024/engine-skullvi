<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $skills = Skill::query()->pluck('id', 'name');

        $programs = [
            [
                'name' => 'Géographie 6e - Togo - Programme APC',
                'slug' => 'geographie-6e-togo',
                'description' => 'Cours de collège du programme APC du Togo.',
                'skills' => [],
            ],
            [
                'name' => 'SVT 6e - Togo - Programme APC',
                'slug' => 'svt-6e-togo',
                'description' => 'Cours de collège du programme APC du Togo.',
                'skills' => [],
            ],
            [
                'name' => 'Français 4e - Togo - Programme APC',
                'slug' => 'francais-4e-togo',
                'description' => 'Cours de collège du programme APC du Togo.',
                'skills' => [],
            ],
            [
                'name' => 'SVT 5e - Togo - Programme APC',
                'slug' => 'svt-5e-togo',
                'description' => 'Cours de collège du programme APC du Togo : le vivant, la santé, la nutrition, la reproduction et la géologie du Togo.',
                'skills' => [
                    'Les êtres vivants et leur environnement',
                    'L\'Homme et sa santé',
                    'La nutrition et l\'alimentation des êtres vivants',
                    'La reproduction des êtres vivants et l\'hérédité',
                    'La géodynamique et la géologie du Togo',
                ],
            ],
            [
                'name' => 'Les migrations et les mutations de la société africaine',
                'slug' => 'migrations-societe-africaine',
                'description' => 'Cours de lycée sur les migrations, l\'exode rural et les mutations sociales en Afrique.',
                'skills' => [
                    'Les notions de migration et de mutation sociale',
                    'Les causes, opportunités et défis des migrations',
                    'Migrations et transformations sociales',
                ],
            ],
            [
                'name' => 'Science et Technologie : Progrès ou Danger ?',
                'slug' => 'science-technologie-progres-ou-danger',
                'description' => 'Cours de lycée.',
                'skills' => [],
            ],
            [
                'name' => 'Mathématiques',
                'slug' => 'mathematiques',
                'description' => 'Cours de lycée.',
                'skills' => [],
            ],
            [
                'name' => 'Physique',
                'slug' => 'physique',
                'description' => 'Cours de lycée sur le mouvement, l\'énergie, les ondes et la matière.',
                'skills' => [
                    'Mouvement et forces',
                    'Systèmes d\'énergie et d\'alimentation',
                    'Lumière, son et ondes',
                    'Le domaine atomique et nucléaire',
                ],
            ],
            [
                'name' => 'Intelligence artificielle',
                'slug' => 'intelligence-artificielle',
                'description' => 'Formation recommandée sur la logique des machines, les prompts, l\'automatisation et l\'éthique.',
                'skills' => [
                    'La logique de la machine',
                    'Ingénierie avancée des prompts',
                    'Flux de travail et automatisation basés sur l\'IA',
                    'Éthique et innovation',
                ],
            ],
            [
                'name' => 'Technologie',
                'slug' => 'technologie',
                'description' => 'Formation recommandée sur le Web, l\'expérience utilisateur, les données et l\'automatisation.',
                'skills' => [
                    'Fondements numériques et logique Web',
                    'Conception centrée sur l\'utilisateur',
                    'Maîtrise des données et le cloud',
                    'IA et la frontière de l\'automatisation',
                ],
            ],
            [
                'name' => 'Marketing digital',
                'slug' => 'marketing-digital',
                'description' => 'Formation recommandée sur la marque, les réseaux sociaux, la publicité et la conversion.',
                'skills' => [
                    'Identité de marque et stratégie de contenu',
                    'Les réseaux sociaux et l\'algorithme',
                    'Publicités payantes et marketing à la performance',
                    'Analyse et optimisation de la conversion',
                ],
            ],
            [
                'name' => 'Microsoft Excel',
                'slug' => 'microsoft-excel',
                'description' => 'Formation recommandée sur les cellules, les formules, les graphiques et les tableaux croisés dynamiques.',
                'skills' => [
                    'Maîtrise des cellules',
                    'Formules et fonctions logiques',
                    'Visualisation des données et graphiques',
                    'Tableaux croisés dynamiques et automatisation',
                ],
            ],
        ];

        $slugs = [];

        foreach ($programs as $program) {
            $slugs[] = $program['slug'];

            $record = Program::query()->updateOrCreate(
                ['slug' => $program['slug']],
                [
                    'name' => $program['name'],
                    'description' => $program['description'],
                    'is_open' => true,
                ],
            );

            $record->skills()->sync(collect($program['skills'])->mapWithKeys(
                fn (string $name) => [$skills[$name] => ['is_required' => true]],
            )->all());
        }

        Program::query()->whereNotIn('slug', $slugs)->delete();
    }
}
