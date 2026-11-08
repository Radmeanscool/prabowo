<?php

namespace App\Notifications;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposalStatusChanged extends Notification
{
    public function __construct(public Proposal $proposal, public ProposalStatus $status) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Status proposal DEEPNEST: '.$this->status->label())
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Status proposal "'.$this->proposal->product_name.'" kini: '.$this->status->label().'.');

        if ($this->status === ProposalStatus::RevisionRequested) {
            $message->line('Panitia membuka kembali proposal Anda untuk revisi. Silakan perbarui isian lalu pilih "Ajukan Revisi".');
        }

        return $message
            ->action('Buka Portal Peserta', route('portal.proposal.status'))
            ->salutation('Salam, Tim Pelaksana DEEPNEST');
    }
}
