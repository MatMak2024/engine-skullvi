<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification
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
        $this->application->loadMissing(['candidate.user', 'program', 'course', 'offer']);

        return [
            'application_id' => $this->application->id,
            'title' => 'Nouvelle candidature',
            'body' => $this->application->candidate->user->displayName()
                .' a postulé à '.$this->application->targetName().'.',
            'href' => route('admin.applications.show', $this->application),
        ];
    }
}
