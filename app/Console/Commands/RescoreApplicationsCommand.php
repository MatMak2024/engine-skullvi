<?php

namespace App\Console\Commands;

use App\Services\Scoring\ScoringService;
use Illuminate\Console\Command;

class RescoreApplicationsCommand extends Command
{
    protected $signature = 'applications:rescore {--program= : Limit the rescore to one program}';

    protected $description = 'Rescore every application with the current rubric';

    public function handle(ScoringService $scoring): int
    {
        $programId = $this->option('program');
        $count = $scoring->rescoreAll(is_string($programId) && $programId !== '' ? $programId : null);

        $this->components->info($count.' candidature(s) recalculée(s).');

        return self::SUCCESS;
    }
}
