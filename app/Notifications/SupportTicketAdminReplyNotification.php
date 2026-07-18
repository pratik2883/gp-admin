<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Notifications\Channels\FcmChannel;
use App\Settings\NotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketAdminReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public SupportTicketMessage $message,
    ) {}

    public function via(object $notifiable): array
    {
        $settings = app(NotificationSettings::class);
        $channels = [];

        if ($settings->in_app_enabled) {
            $channels[] = 'database';
        }

        if (! method_exists($notifiable, 'allowsNotificationChannel')) {
            if ($settings->email_enabled) {
                $channels[] = 'mail';
            }

            if ($settings->push_enabled) {
                $channels[] = FcmChannel::class;
            }

            return array_values(array_unique($channels));
        }

        if ($settings->email_enabled && $notifiable->allowsNotificationChannel('email')) {
            $channels[] = 'mail';
        }

        if ($settings->push_enabled && $notifiable->allowsNotificationChannel('push')) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function viaQueues(): array
    {
        return [
            'database' => 'notifications',
            'mail' => 'notifications',
            FcmChannel::class => 'notifications',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Support ticket update: '.$this->ticket->ticket_no)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Our support team replied to your ticket.')
            ->line('Ticket: '.$this->ticket->ticket_no)
            ->line('Subject: '.$this->ticket->subject)
            ->line('Latest reply: '.str($this->message->message ?? 'Please open the app to review the update.')->limit(160))
            ->action('View ticket', url('/admin'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Support replied',
            'body' => 'Your support ticket '.$this->ticket->ticket_no.' has a new reply.',
            'format' => 'filament',
            'duration' => 'persistent',
            'target_id' => $this->ticket->id,
            'ticket_no' => $this->ticket->ticket_no,
            'status' => $this->ticket->status,
            'type' => 'support_ticket_reply',
        ];
    }
}
