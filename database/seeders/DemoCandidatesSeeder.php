<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\Role as RoleName;
use App\Enums\SkillLevel;
use App\Models\Program;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use App\Services\Scoring\ScoringService;
use Illuminate\Database\Seeder;

class DemoCandidatesSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::query()->where('slug', 'intelligence-artificielle')->firstOrFail();
        $skills = Skill::query()->pluck('id', 'name');
        $scoring = app(ScoringService::class);

        $this->apply(
            $scoring,
            $program,
            'Amina Diallo',
            'amina@talent-engine.test',
            [
                'phone' => '+221770000001',
                'country' => 'Sénégal',
                'city' => 'Dakar',
            ],
            [
                'education_level' => EducationLevel::Master,
                'field_of_study' => 'Intelligence artificielle',
                'years_experience' => 5,
                'projects_count' => 5,
                'github_url' => 'https://github.com/amina',
                'portfolio_url' => 'https://amina.dev',
                'linkedin_url' => 'https://www.linkedin.com/in/amina',
                'availability' => Availability::Immediate,
                'motivation_text' => $this->motivation('Je pratique la logique des machines, les prompts, l\'automatisation et l\'éthique. '),
                'cv_path' => 'cvs/amina.pdf',
                'submitted_at' => now()->subDays(3),
            ],
            [
                $skills['La logique de la machine'] => SkillLevel::Advanced,
                $skills['Ingénierie avancée des prompts'] => SkillLevel::Advanced,
                $skills['Flux de travail et automatisation basés sur l\'IA'] => SkillLevel::Advanced,
                $skills['Éthique et innovation'] => SkillLevel::Advanced,
            ],
        );

        $this->apply(
            $scoring,
            $program,
            'Luc Bernard',
            'luc@talent-engine.test',
            [
                'country' => 'France',
                'city' => 'Lyon',
            ],
            [
                'education_level' => EducationLevel::Licence,
                'field_of_study' => 'Intelligence artificielle',
                'years_experience' => 3,
                'projects_count' => 3,
                'github_url' => 'https://github.com/luc',
                'linkedin_url' => 'https://www.linkedin.com/in/luc',
                'availability' => Availability::OneMonth,
                'motivation_text' => $this->motivation('Je veux progresser sur les prompts et l\'automatisation. ', 220),
                'submitted_at' => now()->subDays(2),
            ],
            [
                $skills['La logique de la machine'] => SkillLevel::Intermediate,
                $skills['Ingénierie avancée des prompts'] => SkillLevel::Advanced,
                $skills['Flux de travail et automatisation basés sur l\'IA'] => SkillLevel::Intermediate,
                $skills['Éthique et innovation'] => SkillLevel::Beginner,
            ],
        );

        $this->apply(
            $scoring,
            $program,
            'Sara Ndiaye',
            'sara@talent-engine.test',
            [
                'country' => 'Sénégal',
                'city' => 'Thiès',
            ],
            [
                'education_level' => EducationLevel::Bac,
                'years_experience' => 0,
                'projects_count' => 0,
                'availability' => Availability::Later,
                'motivation_text' => 'Je suis motivée.',
                'submitted_at' => now()->subDay(),
            ],
            [
                $skills['La logique de la machine'] => SkillLevel::Beginner,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $application
     * @param  array<string, SkillLevel>  $levels
     */
    private function apply(
        ScoringService $scoring,
        Program $program,
        string $name,
        string $email,
        array $profile,
        array $application,
        array $levels,
    ): void {
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $user = User::factory()->create([
                ...User::splitName($name),
                'email' => $email,
            ]);
        }

        $role = Role::query()->where('slug', RoleName::Candidate)->first();

        if ($role instanceof Role && ! $user->roles()->whereKey($role->id)->exists()) {
            $user->roles()->attach($role);
        }

        $candidate = $user->candidate()->updateOrCreate([], $profile);
        $candidate->applications()->where('program_id', $program->id)->delete();

        $record = $candidate->applications()->create([
            'program_id' => $program->id,
            'status' => ApplicationStatus::Received,
            ...$application,
        ]);

        $record->skills()->attach(collect($levels)->map(
            fn (SkillLevel $level) => ['level' => $level->value],
        )->all());

        $record->forceFill(['examination_authorized_at' => now()])->save();
        $scoring->score($record);
    }

    private function motivation(string $intro, int $length = 420): string
    {
        $text = $intro;

        while (mb_strlen($text) < $length) {
            $text .= 'Projet livre en equipe et suivi jusqu\'a la mise en ligne. ';
        }

        return mb_substr($text, 0, $length);
    }
}
