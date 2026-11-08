<?php

namespace App\Notifications;

use App\Models\ReviewerAssignment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewerAssigned extends Notification
{
    public function __construct(public ReviewerAssignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Penugasan penilaian DEEPNEST')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Anda ditugaskan menilai satu proposal pada fase '.$this->assignment->phase?->name.'.');

        if ($this->assignment->due_at) {
            $message->line('Batas penilaian: '.fmt_date($this->assignment->due_at, true).'.');
        }

        return $message
            ->line('Jika Anda memiliki konflik kepentingan, nyatakan melalui portal reviewer sebelum menilai.')
            ->action('Buka Portal Reviewer', route('reviewer.assignments.index'))
            ->salutation('Salam, Tim Pelaksana DEEPNEST');
    }
}
