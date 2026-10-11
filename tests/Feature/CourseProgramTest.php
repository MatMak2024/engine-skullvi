<?php

namespace Tests\Feature;

use App\Enums\EducationLevel;
use App\Enums\ScoringCriterion;
use App\Models\Application;
use App\Models\Course;
use App\Models\Program;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class CourseProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_course_belongs_to_a_program_and_keeps_its_skills(): void
    {
        $this->assertTrue(Schema::hasTable('course_skill'));
        $this->assertTrue(Schema::hasColumn('courses', 'program_id'));
        $this->assertFalse(Schema::hasColumn('programs', 'course_id'));

        $recruiter = TalentWorld::recruiter();
        $program = TalentWorld::program([
            'name' => 'Parcours web',
            'slug' => 'parcours-web',
        ]);
        $skill = Skill::query()->create(['name' => 'Laravel']);

        $this->actingAs($recruiter)
            ->post(route('admin.courses.store'), [
                'name' => 'Cours Laravel',
                'description' => 'Les bases de Laravel.',
                'is_open' => '1',
                'program_id' => $program->id,
                'required_skills' => [$skill->id],
            ])
            ->assertRedirect();

        $course = Course::query()->where('name', 'Cours Laravel')->first();
        $this->assertNotNull($course);
        $this->assertSame($program->id, $course->program_id);
        $this->assertTrue((bool) $course->skills()->first()?->pivot->is_required);

        $this->actingAs($recruiter)
            ->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('Cours Laravel');

        $this->actingAs($recruiter)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Laravel')
            ->assertSee('Parcours web');
    }

    public function test_a_program_application_uses_the_skills_of_its_courses(): void
    {
        $program = TalentWorld::program([], ['PHP']);
        $skill = Skill::query()->create(['name' => 'Laravel']);
        $course = Course::query()->create([
            'name' => 'Cours Laravel',
            'slug' => 'cours-laravel',
            'is_open' => true,
            'program_id' => $program->id,
        ]);
        $course->skills()->attach($skill->id, ['is_required' => true]);

        $this->actingAs(User::factory()->create())
            ->post(route('applications.store', $program), [
                'country' => 'Sénégal',
                'city' => 'Dakar',
                'education_level' => EducationLevel::Licence->value,
                'years_experience' => 0,
                'projects_count' => 0,
                'availability' => 'later',
                'motivation_text' => 'Je veux apprendre Laravel dans ce programme.',
                'skills' => [
                    ['id' => $skill->id, 'level' => 'advanced'],
                ],
            ])
            ->assertRedirect();

        $application = Application::query()->where('program_id', $program->id)->first();
        $this->actingAs(TalentWorld::recruiter())
            ->patch(route('admin.applications.examination', $application))
            ->assertRedirect();
        $application?->refresh();
        $score = $application?->currentEvaluation?->scores()
            ->where('criterion', ScoringCriterion::Skills)
            ->first();

        $this->assertNotNull($score);
        $this->assertEquals(25, (float) $score->points);
    }

    public function test_a_program_links_its_free_courses_and_rejects_a_course_already_taken(): void
    {
        $recruiter = TalentWorld::recruiter();
        $free = Course::query()->create([
            'name' => 'Cours libre',
            'slug' => 'cours-libre',
            'is_open' => true,
        ]);
        $takenProgram = TalentWorld::program([
            'name' => 'Autre parcours',
            'slug' => 'autre-parcours',
        ]);
        $taken = Course::query()->create([
            'name' => 'Cours pris',
            'slug' => 'cours-pris',
            'is_open' => true,
            'program_id' => $takenProgram->id,
        ]);

        $this->actingAs($recruiter)
            ->post(route('admin.programs.store'), [
                'name' => 'Parcours complet',
                'is_open' => '1',
                'work_mode' => 'remote',
                'course_ids' => [$free->id],
            ])
            ->assertRedirect();

        $program = Program::query()->where('slug', 'parcours-complet')->first();

        $this->assertNotNull($program);
        $this->assertSame($program->id, $free->fresh()->program_id);

        $this->actingAs($recruiter)
            ->put(route('admin.programs.update', $program), [
                'name' => 'Parcours complet',
                'is_open' => '1',
                'work_mode' => 'remote',
                'course_ids' => [$taken->id],
            ])
            ->assertSessionHasErrors('course_ids');

        $this->assertSame($takenProgram->id, $taken->fresh()->program_id);
    }

    public function test_a_recruiter_suspends_a_course_and_can_delete_it_without_applications(): void
    {
        $recruiter = TalentWorld::recruiter();
        $course = Course::query()->create([
            'name' => 'Cours libre',
            'slug' => 'cours-libre',
            'is_open' => true,
        ]);
        $user = User::factory()->create();

        $this->actingAs($recruiter)
            ->patch(route('admin.courses.suspension', $course))
            ->assertRedirect();

        $this->assertFalse($course->fresh()->is_open);

        $this->actingAs($user)
            ->get(route('courses.apply', $course))
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertModelMissing($course);
    }

    public function test_a_recruiter_suspends_a_program_and_can_delete_it_without_courses(): void
    {
        $recruiter = TalentWorld::recruiter();
        $program = TalentWorld::program([
            'name' => 'Parcours libre',
            'slug' => 'parcours-libre',
        ]);
        $user = User::factory()->create();

        $this->actingAs($recruiter)
            ->patch(route('admin.programs.suspension', $program))
            ->assertRedirect();

        $this->assertFalse($program->fresh()->is_open);

        $this->actingAs($user)
            ->get(route('applications.create', $program))
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->delete(route('admin.programs.destroy', $program))
            ->assertRedirect(route('admin.programs.index'));

        $this->assertModelMissing($program);
    }

    public function test_a_suspended_skill_cannot_be_added_and_an_unused_skill_can_be_deleted(): void
    {
        $recruiter = TalentWorld::recruiter();
        $skill = Skill::query()->create(['name' => 'Compétence libre']);

        $this->actingAs($recruiter)
            ->patch(route('admin.skills.suspension', $skill))
            ->assertRedirect();

        $this->assertFalse($skill->fresh()->is_open);

        $this->actingAs($recruiter)
            ->post(route('admin.courses.store'), [
                'name' => 'Cours interdit',
                'is_open' => '1',
                'required_skills' => [$skill->id],
            ])
            ->assertSessionHasErrors('required_skills');

        $this->actingAs($recruiter)
            ->delete(route('admin.skills.destroy', $skill))
            ->assertRedirect(route('admin.skills.index'));

        $this->assertModelMissing($skill);
    }
}
