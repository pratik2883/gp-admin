<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    public bool $sms_enabled = false;

    public bool $whatsapp_enabled = false;

    public bool $email_enabled = true;

    public bool $in_app_enabled = true;

    public bool $push_enabled = false;

    public ?string $twilio_account_sid = null;

    public ?string $twilio_auth_token = null;

    public ?string $twilio_from_number = null;

    public ?string $twilio_whatsapp_from = null;

    public ?string $twilio_verify_service_sid = null;

    public bool $message_central_sms_enabled = false;

    public bool $message_central_whatsapp_enabled = false;

    public ?string $message_central_customer_id = null;

    public ?string $message_central_password = null;

    public ?string $message_central_email = null;

    public ?string $message_central_sms_sender_id = null;

    public ?string $message_central_whatsapp_sender_id = null;

    public ?string $mail_from_name = null;

    public ?string $mail_from_address = null;

    public ?string $mail_host = null;

    public ?int $mail_port = null;

    public ?string $mail_username = null;

    public ?string $mail_password = null;

    public string $mail_encryption = 'tls';

    /**
     * ['referral_created' => ['email','in_app'], ...]
     */
    public array $events_channels = [];

    /**
     * [['event' => 'subscription_activated', 'mail_subject' => '...', ...], ...]
     */
    public array $event_templates = [];

    public static function group(): string
    {
        return 'notifications';
    }

    public function channelsFor(string $event): array
    {
        foreach ($this->events_channels as $row) {
            if (($row['event'] ?? null) === $event) {
                $channels = $row['channels'] ?? [];

                return array_values(array_filter($channels, function ($channel) {
                    return match ($channel) {
                        'email' => $this->email_enabled,
                        'sms' => $this->sms_enabled,
                        'whatsapp' => $this->whatsapp_enabled,
                        'in_app' => $this->in_app_enabled,
                        'push' => $this->push_enabled,
                        'mc_sms' => $this->message_central_sms_enabled,
                        'mc_whatsapp' => $this->message_central_whatsapp_enabled,
                        default => false,
                    };
                }));
            }
        }

        $defaults = [
            'referral_created' => ['email', 'in_app'],
            'referral_accepted' => ['email', 'in_app'],
            'referral_consulted' => ['email', 'in_app'],
            'referral_closed' => ['email', 'in_app'],
            'new_gp_registered' => ['email', 'in_app'],
            'new_specialist_registered' => ['email', 'in_app'],
            'subscription_payment_pending' => ['email', 'in_app'],
            'subscription_activated' => ['email', 'in_app', 'push'],
            'subscription_payment_failed' => ['email', 'in_app'],
            'subscription_expired' => ['email', 'in_app', 'push'],
            'subscription_cancelled' => ['email', 'in_app'],
        ];

        $fallback = $defaults[$event] ?? [];

        return array_values(array_filter($fallback, function ($channel) {
            return match ($channel) {
                'email' => $this->email_enabled,
                'sms' => $this->sms_enabled,
                'whatsapp' => $this->whatsapp_enabled,
                'in_app' => $this->in_app_enabled,
                'push' => $this->push_enabled,
                'mc_sms' => $this->message_central_sms_enabled,
                'mc_whatsapp' => $this->message_central_whatsapp_enabled,
                default => false,
            };
        }));
    }

    public function templateFor(string $event): array
    {
        foreach ($this->event_templates as $row) {
            if (($row['event'] ?? null) === $event) {
                return is_array($row) ? $row : [];
            }
        }

        return [];
    }
}
