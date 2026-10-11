<?php

namespace App\Services\Admin;

use App\Enums\Role as RoleName;
use App\Models\Candidate;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CandidateSanctionNotification;
use Illuminate\Support\Facades\DB;

class ManageCandidateService
{
    public function suspend(Candidate $candidate, string $reason): void
    {
        $candidate->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();

        $candidate->user?->notify(new CandidateSanctionNotification(
            'Dossier suspendu',
            'Votre dossier de candidat est suspendu. Situation à résoudre : '.$reason.'. Réglez cette situation pour que le recruteur puisse lever la suspension. Votre compte utilisateur reste actif.',
        ));
    }

    public function lift(Candidate $candidate): void
    {
        $candidate->forceFill([
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();
    }

    public function delete(Candidate $candidate, string $reason): void
    {
        DB::transaction(function () use ($candidate, $reason): void {
            $user = $candidate->user;
            $candidateRole = Role::query()->where('slug', RoleName::Candidate)->first();
            $userRole = Role::query()->firstOrCreate(
                ['slug' => RoleName::User],
                ['name' => 'Utilisateur'],
            );

            if ($user instanceof User) {
                $user->roles()->syncWithoutDetaching([$userRole->id]);

                if ($candidateRole instanceof Role) {
                    $user->roles()->detach($candidateRole->id);
                }

                $user->forceFill([
                    'candidate_removed_at' => now(),
                    'candidate_removal_reason' => $reason,
                ])->save();

                $user->notify(new CandidateSanctionNotification(
                    'Vous n’êtes plus candidat',
                    'Votre compte reste un compte utilisateur, mais vous ne pourrez plus jamais déposer de candidature. Motif : '.$reason.'.',
                ));
            }

            $candidate->delete();
        });
    }
}
