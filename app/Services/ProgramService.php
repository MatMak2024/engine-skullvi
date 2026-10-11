<?php

namespace App\Services;

use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;

class ProgramService
{
    /**
     * @return Collection<int, Program>
     */
    public function open(): Collection
    {
        return Program::query()
            ->where('is_open', true)
            ->withCount('courses')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Program>
     */
    public function all(): Collection
    {
        return Program::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function withSkills(Program $program): Program
    {
        $program->load(['skills', 'courses.skills']);

        return $program;
    }
}
