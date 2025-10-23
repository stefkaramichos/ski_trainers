<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketReplied extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public TicketMessage $message) {}

    public function via($notifiable): array
    {
        return ['mail']; // add 'database' if you use notifications table
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New reply on Ticket #'.$this->ticket->id)
            ->greeting('Hello '.$notifiable->name)
            ->line('There is a new message on "'.$this->ticket->subject.'".')
            ->action('View Ticket', route('tickets.show',$this->ticket))
            ->line('Message: "'.$this->message->body.'"');
    }
}
