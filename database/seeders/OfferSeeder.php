<?php

namespace Database\Seeders;

use App\Enums\WorkMode;
use App\Models\Offer;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $skills = Skill::query()->pluck('id', 'name');

        $offers = [
            [
                'name' => 'Formateur en intelligence artificielle',
                'slug' => 'formateur-intelligence-artificielle',
                'description' => 'Accompagner les apprenants sur la logique des machines, les prompts et l’automatisation.',
                'work_mode' => WorkMode::Remote,
                'work_country' => null,
                'work_city' => null,
                'required' => [
                    'La logique de la machine',
                    'Ingénierie avancée des prompts',
                ],
                'bonus' => [
                    'Éthique et innovation',
                ],
            ],
            [
                'name' => 'Concepteur web',
                'slug' => 'concepteur-web',
                'description' => 'Concevoir des parcours numériques clairs, du fondement web à l’expérience utilisateur.',
                'work_mode' => WorkMode::Hybrid,
                'work_country' => 'Togo',
                'work_city' => 'Lomé',
                'required' => [
                    'Fondements numériques et logique Web',
                    'Conception centrée sur l\'utilisateur',
                ],
                'bonus' => [
                    'Maîtrise des données et le cloud',
                ],
            ],
            [
                'name' => 'Chargé de marketing digital',
                'slug' => 'charge-marketing-digital',
                'description' => 'Porter la marque, les réseaux sociaux et la conversion des formations.',
                'work_mode' => WorkMode::Remote,
                'work_country' => null,
                'work_city' => null,
                'required' => [
                    'Identité de marque et stratégie de contenu',
                    'Les réseaux sociaux et l\'algorithme',
                ],
                'bonus' => [
                    'Analyse et optimisation de la conversion',
                ],
            ],
            [
                'name' => 'Formateur Microsoft Excel',
                'slug' => 'formateur-microsoft-excel',
                'description' => 'Former aux formules, aux graphiques et aux tableaux croisés dynamiques.',
                'work_mode' => WorkMode::Onsite,
                'work_country' => 'Belgique',
                'work_city' => 'Enghien',
                'required' => [
                    'Formules et fonctions logiques',
                    'Tableaux croisés dynamiques et automatisation',
                ],
                'bonus' => [
                    'Visualisation des données et graphiques',
                ],
            ],
            [
                'name' => 'Enseignant de physique',
                'slug' => 'enseignant-physique',
                'description' => 'Enseigner le mouvement, l’énergie, les ondes et la matière au lycée.',
                'work_mode' => WorkMode::Onsite,
                'work_country' => 'Togo',
                'work_city' => 'Lomé',
                'required' => [
                    'Mouvement et forces',
                    'Lumière, son et ondes',
                ],
                'bonus' => [
                    'Le domaine atomique et nucléaire',
                ],
            ],
        ];

        foreach ($offers as $offer) {
            $record = Offer::query()->updateOrCreate(
                ['slug' => $offer['slug']],
                [
                    'name' => $offer['name'],
                    'description' => $offer['description'],
                    'is_open' => true,
                    'work_mode' => $offer['work_mode'],
                    'work_country' => $offer['work_country'],
                    'work_city' => $offer['work_city'],
                ],
            );

            $sync = [];

            foreach ($offer['required'] as $name) {
                $sync[$skills[$name]] = ['is_required' => true];
            }

            foreach ($offer['bonus'] as $name) {
                $sync[$skills[$name]] = ['is_required' => false];
            }

            $record->skills()->sync($sync);
        }
    }
}
