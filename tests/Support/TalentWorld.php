<?php

namespace Tests\Support;

use App\Enums\ApplicationStatus;
use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\Role as RoleName;
use App\Models\Application;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;

class TalentWorld
{
    /**
     * @param  list<string>  $required
     * @param  list<string>  $bonus
     * @param  array<string, mixed>  $attributes
     */
    public static function program(array $attributes = [], array $required = [], array $bonus = []): Program
    {
        $program = Program::query()->create([
            'name' => 'Dev',
            'slug' => 'dev-'.str()->random(8),
            'description' => 'laravel react',
            'is_open' => true,
            ...$attributes,
        ]);

        $attach = [];

        foreach ($required as $name) {
            $skill = Skill::query()->create(['name' => $name]);
            $attach[$skill->id] = ['is_required' => true];
        }

        foreach ($bonus as $name) {
            $skill = Skill::query()->create(['name' => $name]);
            $attach[$skill->id] = ['is_required' => false];
        }

        if ($attach !== []) {
            $program->skills()->attach($attach);
        }

        return $program->load('skills');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $skillLevels
     */
    public static function application(Program $program, array $attributes = [], array $skillLevels = []): Application
    {
        $user = User::factory()->create();
        $candidate = $user->candidate()->create([
            'phone' => $attributes['phone'] ?? null,
            'country' => array_key_exists('country', $attributes) ? $attributes['country'] : 'Sénégal',
            'city' => array_key_exists('city', $attributes) ? $attributes['city'] : 'Dakar',
        ]);

        unset($attributes['phone'], $attributes['country'], $attributes['city']);

        $application = $candidate->applications()->create([
            'program_id' => $program->id,
            'education_level' => EducationLevel::None,
            'years_experience' => 0,
            'projects_count' => 0,
            'availability' => Availability::Later,
            'motivation_text' => 'Motivation de test suffisamment longue pour le dossier.',
            'status' => ApplicationStatus::Received,
            'submitted_at' => now(),
            ...$attributes,
        ]);

        if ($skillLevels !== []) {
            $application->skills()->attach(collect($skillLevels)->map(
                fn (string $level) => ['level' => $level],
            )->all());
        }

        return $application->fresh(['skills', 'program.skills', 'candidate']);
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function recruiter(array $permissions = [
        'application:view',
        'application:update-status',
        'review-note:create',
    ]): User
    {
        $user = User::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['slug' => RoleName::Recruiter],
            ['name' => 'Recruteur'],
        );

        foreach ($permissions as $name) {
            $permission = Permission::query()->firstOrCreate(['name' => $name]);
            $role->permissions()->syncWithoutDetaching($permission);
        }

        $user->roles()->syncWithoutDetaching($role);

        return $user;
    }

    public static function text(string $intro, int $length): string
    {
        $text = $intro;

        while (mb_strlen($text) < $length) {
            $text .= 'Je suis disponible et motive. ';
        }

        return mb_substr($text, 0, $length);
    }
}
