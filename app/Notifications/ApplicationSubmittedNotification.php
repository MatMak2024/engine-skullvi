<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationSubmittedNotification extends Notification
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
            'title' => 'Candidature envoyée',
            'body' => 'Votre candidature pour « '.$this->application->targetName()
                .' » a bien été reçue.',
            'href' => route('applications.show', $this->application),
        ];
    }
}
