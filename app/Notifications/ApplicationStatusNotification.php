<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private Application $application) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        $this->application->loadMissing(['program', 'course', 'offer']);

        return [
            'application_id' => $this->application->id,
            'title' => 'Statut de candidature mis à jour',
            'body' => 'Votre candidature « '.$this->application->targetName()
                .' » est maintenant : '.$this->statusLabel($this->application->status).'.',
            'href' => route('applications.show', $this->application),
        ];
    }

    private function statusLabel(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::Received => 'reçue',
            ApplicationStatus::Incomplete => 'incomplète',
            ApplicationStatus::Ineligible => 'non éligible',
            ApplicationStatus::Priority => 'prioritaire',
            ApplicationStatus::ToReview => 'à examiner',
            ApplicationStatus::LowFit => 'adéquation faible',
            ApplicationStatus::Accepted => 'acceptée',
            ApplicationStatus::Rejected => 'refusée',
        };
    }
}
