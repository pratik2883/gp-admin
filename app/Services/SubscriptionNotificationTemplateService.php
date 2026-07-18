<?php

namespace App\Services;

use App\Settings\NotificationSettings;

class SubscriptionNotificationTemplateService
{
    public function __construct(
        private readonly NotificationSettings $settings,
    ) {}

    public function build(string $event, array $context): array
    {
        $template = $this->settings->templateFor($event);
        $defaults = $this->defaultTemplate($event);

        $resolved = [];
        foreach (['mail_subject', 'mail_body', 'in_app_title', 'in_app_body', 'sms_text', 'whatsapp_text'] as $key) {
            $resolved[$key] = $this->render(
                (string) (($template[$key] ?? null) ?: ($defaults[$key] ?? '')),
                $context,
            );
        }

        return $resolved;
    }

    private function render(string $value, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $item) {
            if ($item === null || is_array($item) || is_object($item)) {
                continue;
            }

            $replacements['{{'.$key.'}}'] = (string) $item;
        }

        return trim(strtr($value, $replacements));
    }

    private function defaultTemplate(string $event): array
    {
        return match ($event) {
    'referral_created' => [
        'mail_subject' => 'New referral created - {{lead_code}}',
        'mail_body' => "Hello {{user_name}},\n\nA new referral has been created.\nLead: {{lead_code}}\nPatient: {{patient_name}}\nSpecialist: {{specialist_name}}\nGP: {{gp_name}}\nPriority: {{priority}}",
        'in_app_title' => 'New referral created',
        'in_app_body' => 'Referral {{lead_code}} for {{patient_name}} sent to {{specialist_name}}.',
        'sms_text' => 'New referral {{lead_code}} for {{patient_name}}.',
        'whatsapp_text' => 'Hello {{user_name}}, referral {{lead_code}} for {{patient_name}} has been created.',
    ],
    'referral_accepted' => [
        'mail_subject' => 'Referral accepted - {{lead_code}}',
        'mail_body' => "Hello {{user_name}},\n\nYour referral {{lead_code}} for {{patient_name}} has been accepted by {{specialist_name}}.",
        'in_app_title' => 'Referral accepted',
        'in_app_body' => '{{specialist_name}} accepted referral {{lead_code}} for {{patient_name}}.',
        'sms_text' => 'Referral {{lead_code}} accepted by {{specialist_name}}.',
        'whatsapp_text' => 'Hello {{user_name}}, referral {{lead_code}} has been accepted by {{specialist_name}}.',
    ],
    'referral_consulted' => [
        'mail_subject' => 'Referral consulted - {{lead_code}}',
        'mail_body' => "Hello {{user_name}},\n\nYour referral {{lead_code}} for {{patient_name}} has been marked as consulted by {{specialist_name}}.",
        'in_app_title' => 'Referral consulted',
        'in_app_body' => '{{specialist_name}} consulted referral {{lead_code}} for {{patient_name}}.',
        'sms_text' => 'Referral {{lead_code}} consulted.',
        'whatsapp_text' => 'Hello {{user_name}}, referral {{lead_code}} has been consulted.',
    ],
    'referral_closed' => [
        'mail_subject' => 'Referral closed - {{lead_code}}',
        'mail_body' => "Hello {{user_name}},\n\nYour referral {{lead_code}} for {{patient_name}} has been closed.\nStatus: {{status}}",
        'in_app_title' => 'Referral closed',
        'in_app_body' => 'Referral {{lead_code}} for {{patient_name}} has been closed.',
        'sms_text' => 'Referral {{lead_code}} closed.',
        'whatsapp_text' => 'Referral {{lead_code}} for {{patient_name}} has been closed.',
    ],
    'new_gp_registered' => [
        'mail_subject' => 'New GP registered - {{user_name}}',
        'mail_body' => "Hello Admin,\n\nA new GP has registered.\nName: {{user_name}}\nMobile: {{mobile}}\nEmail: {{email}}\nCity: {{city}}",
        'in_app_title' => 'New GP registered',
        'in_app_body' => '{{user_name}} registered as a new GP.',
        'sms_text' => 'New GP {{user_name}} registered.',
        'whatsapp_text' => 'New GP {{user_name}} has registered.',
    ],
    'new_specialist_registered' => [
        'mail_subject' => 'New specialist registered - {{user_name}}',
        'mail_body' => "Hello Admin,\n\nA new specialist has registered.\nName: {{user_name}}\nSpecialty: {{specialty}}\nCity: {{city}}",
        'in_app_title' => 'New specialist registered',
        'in_app_body' => '{{user_name}} registered as a new specialist ({{specialty}}).',
        'sms_text' => 'New specialist {{user_name}} registered.',
        'whatsapp_text' => 'New specialist {{user_name}} ({{specialty}}) has registered.',
    ],
    'subscription_payment_pending' => [
        'mail_subject' => 'Subscription payment pending for {{plan_name}}',
        'mail_body' => "Hello {{user_name}},\nYour subscription payment is pending for {{plan_name}}.\nAmount: {{amount}} {{currency}}\nOpen this link to complete payment: {{action_url}}",
        'in_app_title' => 'Complete subscription payment',
        'in_app_body' => 'Your {{plan_name}} payment of {{amount}} {{currency}} is pending.',
        'sms_text' => 'Payment pending for {{plan_name}}. Amount {{amount}} {{currency}}. Complete here: {{action_url}}',
        'whatsapp_text' => 'Hello {{user_name}}, your payment for {{plan_name}} is pending. Amount: {{amount}} {{currency}}. Pay here: {{action_url}}',
    ],
    'subscription_activated' => [
        'mail_subject' => 'Subscription activated: {{plan_name}}',
        'mail_body' => "Hello {{user_name}},\nYour subscription {{plan_name}} is now active.\nValid till: {{ends_at}}",
        'in_app_title' => 'Subscription activated',
        'in_app_body' => 'Your {{plan_name}} subscription is active till {{ends_at}}.',
        'sms_text' => 'Your {{plan_name}} subscription is active till {{ends_at}}.',
        'whatsapp_text' => 'Hello {{user_name}}, your {{plan_name}} subscription is now active till {{ends_at}}.',
    ],
    'subscription_payment_failed' => [
        'mail_subject' => 'Subscription payment failed: {{plan_name}}',
        'mail_body' => "Hello {{user_name}},\nYour payment for {{plan_name}} could not be completed.\nStatus: {{status}}\nRetry here: {{action_url}}",
        'in_app_title' => 'Subscription payment failed',
        'in_app_body' => 'Payment for {{plan_name}} failed. You can retry from the app.',
        'sms_text' => 'Payment for {{plan_name}} failed. Retry from the app.',
        'whatsapp_text' => 'Hello {{user_name}}, payment for {{plan_name}} failed. Status: {{status}}. Retry from the app.',
    ],
    'subscription_expired' => [
        'mail_subject' => 'Subscription expired: {{plan_name}}',
        'mail_body' => "Hello {{user_name}},\nYour subscription {{plan_name}} has expired on {{ends_at}}.",
        'in_app_title' => 'Subscription expired',
        'in_app_body' => 'Your {{plan_name}} subscription has expired.',
        'sms_text' => 'Your {{plan_name}} subscription has expired.',
        'whatsapp_text' => 'Hello {{user_name}}, your {{plan_name}} subscription has expired.',
    ],
    'subscription_cancelled' => [
        'mail_subject' => 'Subscription cancelled: {{plan_name}}',
        'mail_body' => "Hello {{user_name}},\nYour subscription {{plan_name}} has been cancelled.",
        'in_app_title' => 'Subscription cancelled',
        'in_app_body' => 'Your {{plan_name}} subscription has been cancelled.',
        'sms_text' => 'Your {{plan_name}} subscription has been cancelled.',
        'whatsapp_text' => 'Hello {{user_name}}, your {{plan_name}} subscription has been cancelled.',
    ],
    default => [
        'mail_subject' => '',
        'mail_body' => '',
        'in_app_title' => '',
        'in_app_body' => '',
        'sms_text' => '',
        'whatsapp_text' => '',
    ],
};
    }
}
