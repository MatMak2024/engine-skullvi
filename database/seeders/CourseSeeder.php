<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $skills = Skill::query()->pluck('id', 'name');
        $programs = Program::query()->pluck('id', 'slug');

        $courses = [
            [
                'name' => 'Prompts et logique des machines',
                'slug' => 'prompts-logique-machines',
                'description' => 'Cours du programme Intelligence artificielle : logique des machines, prompts et automatisation.',
                'program' => 'intelligence-artificielle',
                'required' => [
                    'La logique de la machine',
                    'Ingénierie avancée des prompts',
                ],
                'bonus' => [
                    'Éthique et innovation',
                ],
            ],
            [
                'name' => 'Mouvement, forces et ondes',
                'slug' => 'mouvement-forces-ondes',
                'description' => 'Cours du programme Physique : mouvement, forces, lumière et ondes.',
                'program' => 'physique',
                'required' => [
                    'Mouvement et forces',
                    'Lumière, son et ondes',
                ],
                'bonus' => [
                    'Le domaine atomique et nucléaire',
                ],
            ],
            [
                'name' => 'Marque et réseaux sociaux',
                'slug' => 'marque-reseaux-sociaux',
                'description' => 'Cours du programme Marketing digital : identité de marque, contenus et réseaux sociaux.',
                'program' => 'marketing-digital',
                'required' => [
                    'Identité de marque et stratégie de contenu',
                    'Les réseaux sociaux et l\'algorithme',
                ],
                'bonus' => [
                    'Analyse et optimisation de la conversion',
                ],
            ],
            [
                'name' => 'Fondements du Web',
                'slug' => 'fondements-du-web',
                'description' => 'Cours autonome sur la logique web, l’expérience utilisateur et les données.',
                'program' => null,
                'required' => [
                    'Fondements numériques et logique Web',
                    'Conception centrée sur l\'utilisateur',
                ],
                'bonus' => [
                    'Maîtrise des données et le cloud',
                ],
            ],
            [
                'name' => 'Tableaux croisés dynamiques',
                'slug' => 'tableaux-croises-dynamiques',
                'description' => 'Cours autonome sur les formules, les graphiques et les tableaux croisés dynamiques.',
                'program' => null,
                'required' => [
                    'Formules et fonctions logiques',
                    'Tableaux croisés dynamiques et automatisation',
                ],
                'bonus' => [
                    'Visualisation des données et graphiques',
                ],
            ],
        ];

        foreach ($courses as $course) {
            $record = Course::query()->updateOrCreate(
                ['slug' => $course['slug']],
                [
                    'name' => $course['name'],
                    'description' => $course['description'],
                    'is_open' => true,
                    'program_id' => $course['program'] !== null
                        ? $programs[$course['program']]
                        : null,
                ],
            );

            $sync = [];

            foreach ($course['required'] as $name) {
                $sync[$skills[$name]] = ['is_required' => true];
            }

            foreach ($course['bonus'] as $name) {
                $sync[$skills[$name]] = ['is_required' => false];
            }

            $record->skills()->sync($sync);
        }
    }
}
