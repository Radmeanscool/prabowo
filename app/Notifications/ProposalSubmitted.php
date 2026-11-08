<?php

namespace App\Notifications;

use App\Models\Proposal;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposalSubmitted extends Notification
{
    public function __construct(public Proposal $proposal) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Proposal DEEPNEST diterima: '.$this->proposal->product_name)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Proposal "'.$this->proposal->product_name.'" telah dikirim (versi '.$this->proposal->version.') pada '.fmt_date($this->proposal->submitted_at, true).'.')
            ->line('Proposal yang telah dikirim tidak dapat disunting kecuali panitia membuka kembali untuk revisi.')
            ->action('Lihat Status Proposal', route('portal.proposal.status'))
            ->salutation('Salam, Tim Pelaksana DEEPNEST');
    }
}
