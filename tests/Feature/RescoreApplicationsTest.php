<?php

namespace Tests\Feature;

use App\Models\Evaluation;
use App\Services\Scoring\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class RescoreApplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_command_rescores_every_application_and_can_target_one_program(): void
    {
        $program = TalentWorld::program();
        $other = TalentWorld::program(['name' => 'Autre', 'slug' => 'autre-programme']);
        $first = TalentWorld::application($program, ['years_experience' => 5, 'projects_count' => 5]);
        $second = TalentWorld::application($other, ['years_experience' => 5, 'projects_count' => 5]);

        $scoring = app(ScoringService::class);
        $scoring->score($first);
        $scoring->score($second);

        $first->update(['years_experience' => 0, 'projects_count' => 0]);
        $second->update(['years_experience' => 0, 'projects_count' => 0]);

        $this->artisan('applications:rescore', ['--program' => $program->id])
            ->expectsOutputToContain('1 candidature(s) recalculée(s).')
            ->assertSuccessful();

        $this->assertSame(1, Evaluation::query()->where('application_id', $first->id)->where('is_current', true)->count());
        $this->assertSame(2, Evaluation::query()->where('application_id', $first->id)->count());
        $this->assertSame(1, Evaluation::query()->where('application_id', $second->id)->count());

        $this->artisan('applications:rescore')
            ->expectsOutputToContain('2 candidature(s) recalculée(s).')
            ->assertSuccessful();

        $this->assertSame(2, Evaluation::query()->where('is_current', true)->count());
        $this->assertSame(5, Evaluation::query()->count());
    }
}
