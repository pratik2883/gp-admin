<?php

namespace App\Filament\Pages\Settings;

use App\Settings\NotificationSettings as NotificationSettingsStore;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;

class NotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Notifications';

    protected string $view = 'filament.pages.settings.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $store = app(NotificationSettingsStore::class);
        $this->form->fill([
            'sms_enabled' => $store->sms_enabled,
            'whatsapp_enabled' => $store->whatsapp_enabled,
            'email_enabled' => $store->email_enabled,
            'in_app_enabled' => $store->in_app_enabled,
            'push_enabled' => $store->push_enabled,
            'twilio_account_sid' => $store->twilio_account_sid,
            'twilio_auth_token' => $store->twilio_auth_token,
            'twilio_from_number' => $store->twilio_from_number,
            'twilio_whatsapp_from' => $store->twilio_whatsapp_from,
            'twilio_verify_service_sid' => $store->twilio_verify_service_sid,
            'message_central_sms_enabled' => $store->message_central_sms_enabled,
            'message_central_whatsapp_enabled' => $store->message_central_whatsapp_enabled,
            'message_central_customer_id' => $store->message_central_customer_id,
            'message_central_password' => $store->message_central_password,
            'message_central_email' => $store->message_central_email,
            'message_central_sms_sender_id' => $store->message_central_sms_sender_id,
            'message_central_sms_template_id' => $store->message_central_sms_template_id,
            'message_central_sms_entity_id' => $store->message_central_sms_entity_id,
            'message_central_whatsapp_sender_id' => $store->message_central_whatsapp_sender_id,
            'mail_from_name' => $store->mail_from_name,
            'mail_from_address' => $store->mail_from_address,
            'mail_host' => $store->mail_host,
            'mail_port' => $store->mail_port,
            'mail_username' => $store->mail_username,
            'mail_password' => $store->mail_password,
            'mail_encryption' => $store->mail_encryption,
            'events_channels' => $store->events_channels ?: $this->getDefaultEventChannels(),
            'event_templates' => $store->event_templates,
        ]);
    }

    private function getDefaultEventChannels(): array
    {
        return [
            ['event' => 'referral_created', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'referral_accepted', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'referral_consulted', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'referral_closed', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'referral_rejected', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'diagnostic_referral_created', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'diagnostic_referral_status', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'new_gp_registered', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'new_specialist_registered', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'subscription_payment_pending', 'channels' => ['email', 'in_app']],
            ['event' => 'subscription_activated', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'subscription_payment_failed', 'channels' => ['email', 'in_app']],
            ['event' => 'subscription_expired', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'subscription_cancelled', 'channels' => ['email', 'in_app']],
        ];
    }

    public function form(Schema $schema): Schema
    {
        $whatsappAvailable = class_exists(\App\Notifications\Channels\TwilioWhatsAppChannel::class);

        $eventOptions = [
            'referral_created' => 'Referral created',
            'referral_accepted' => 'Referral accepted',
            'referral_consulted' => 'Referral consulted',
            'referral_closed' => 'Referral closed',
            'referral_rejected' => 'Referral rejected',
            'diagnostic_referral_created' => 'Diagnostic referral created',
            'diagnostic_referral_status' => 'Diagnostic referral status',
            'new_gp_registered' => 'New GP registered',
            'new_specialist_registered' => 'New specialist registered',
            'subscription_payment_pending' => 'Subscription payment pending',
            'subscription_activated' => 'Subscription activated',
            'subscription_payment_failed' => 'Subscription payment failed',
            'subscription_expired' => 'Subscription expired',
            'subscription_cancelled' => 'Subscription cancelled',
        ];

        $channelOptions = [
            'email' => 'Email',
            'sms' => 'SMS',
            'in_app' => 'In-App',
            'push' => 'Push (FCM)',
        ];

        if ($whatsappAvailable) {
            $channelOptions['whatsapp'] = 'WhatsApp';
        }

        $channelOptions['mc_sms'] = 'MC SMS';
        $channelOptions['mc_whatsapp'] = 'MC WhatsApp';

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Channels')
                    ->schema([
                        Forms\Components\Toggle::make('sms_enabled')
                            ->label('Enable SMS')
                            ->inline(false),
                        Forms\Components\Toggle::make('whatsapp_enabled')
                            ->label('Enable WhatsApp')
                            ->inline(false)
                            ->visible($whatsappAvailable),
                        Forms\Components\Toggle::make('email_enabled')
                            ->label('Enable Email')
                            ->inline(false),
                        Forms\Components\Toggle::make('in_app_enabled')
                            ->label('Enable In-App (database) notifications')
                            ->inline(false)
                            ->helperText('Used for admin panel + mobile app bell.'),
                        Forms\Components\Toggle::make('push_enabled')
                            ->label('Enable Push (FCM)')
                            ->inline(false),
                    ])
                    ->columns(2),

                Section::make('Twilio (SMS / WhatsApp / Verify)')
                    ->schema([
                        Forms\Components\TextInput::make('twilio_account_sid')
                            ->label('Account SID')
                            ->password(false),
                        Forms\Components\TextInput::make('twilio_auth_token')
                            ->label('Auth Token')
                            ->password(),
                        Forms\Components\TextInput::make('twilio_from_number')
                            ->label('SMS From Number')
                            ->placeholder('+1...'),
                        Forms\Components\TextInput::make('twilio_whatsapp_from')
                            ->label('WhatsApp From')
                            ->placeholder('whatsapp:+1...')
                            ->visible($whatsappAvailable),
                        Forms\Components\TextInput::make('twilio_verify_service_sid')
                            ->label('Verify Service SID')
                            ->placeholder('VA...')
                            ->helperText('Used for OTP verification via Twilio Verify API.'),
                    ])
                    ->columns(2),

                Section::make('MessageCentral (SMS / WhatsApp)')
                    ->description('Configure MessageCentral for SMS OTP, WhatsApp templates, broadcasts & chat. Get credentials from console.messagecentral.com')
                    ->schema([
                        Forms\Components\Toggle::make('message_central_sms_enabled')
                            ->label('Enable MessageCentral SMS')
                            ->inline(false),
                        Forms\Components\Toggle::make('message_central_whatsapp_enabled')
                            ->label('Enable MessageCentral WhatsApp')
                            ->inline(false),
                        Forms\Components\TextInput::make('message_central_customer_id')
                            ->label('Customer ID')
                            ->placeholder('C-XXXXXXXXXX'),
                        Forms\Components\TextInput::make('message_central_password')
                            ->label('Password')
                            ->password(),
                        Forms\Components\TextInput::make('message_central_email')
                            ->label('Email')
                            ->email()
                            ->placeholder('registered@email.com'),
                        Forms\Components\TextInput::make('message_central_sms_sender_id')
                            ->label('SMS Sender ID')
                            ->placeholder('6-char sender ID'),
                        Forms\Components\TextInput::make('message_central_sms_template_id')
                            ->label('SMS Template ID')
                            ->placeholder('Optional DLT template ID'),
                        Forms\Components\TextInput::make('message_central_sms_entity_id')
                            ->label('SMS Entity ID')
                            ->placeholder('Optional DLT entity ID'),
                        Forms\Components\TextInput::make('message_central_whatsapp_sender_id')
                            ->label('WhatsApp Sender ID (WABA)')
                            ->placeholder('91XXXXXXXXXX'),
                    ])
                    ->columns(2),

                Section::make('Email (SMTP)')
                    ->description('Configure SMTP server for sending emails. Leave blank to use .env defaults.')
                    ->schema([
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('From Name'),
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('From Email')
                            ->email(),
                        Forms\Components\TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.gmail.com'),
                        Forms\Components\TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->placeholder('587'),
                        Forms\Components\TextInput::make('mail_username')
                            ->label('SMTP Username'),
                        Forms\Components\TextInput::make('mail_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable(),
                        Forms\Components\Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => 'NONE',
                            ])
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Events & Channels')
                    ->schema([
                        Forms\Components\Repeater::make('events_channels')
                            ->label('Notification events')
                            ->schema([
                                Forms\Components\Select::make('event')
                                    ->label('Event')
                                    ->options($eventOptions)
                                    ->required()
                                    ->native(false),
                                Forms\Components\CheckboxList::make('channels')
                                    ->label('Channels')
                                    ->options($channelOptions)
                                    ->columns(2),
                            ])
                            ->default([
                                [
                                    'event' => 'referral_created',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                                [
                                    'event' => 'referral_accepted',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                                [
                                    'event' => 'referral_consulted',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                                [
                                    'event' => 'referral_closed',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                                [
                                    'event' => 'referral_rejected',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                                [
                                    'event' => 'diagnostic_referral_created',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                                [
                                    'event' => 'diagnostic_referral_status',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                                [
                                    'event' => 'new_gp_registered',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                                [
                                    'event' => 'new_specialist_registered',
                                    'channels' => ['email', 'in_app', 'push'],
                                ],
                            ])
                            ->columns(1)
                            ->addActionLabel('Add event'),
                    ]),

                Section::make('Event Templates')
                    ->description('Use placeholders like {{user_name}}, {{plan_name}}, {{amount}}, {{currency}}, {{status}}, {{action_url}}.')
                    ->schema([
                        Forms\Components\Repeater::make('event_templates')
                            ->label('Editable templates')
                            ->schema([
                                Forms\Components\Select::make('event')
                                    ->label('Event')
                                    ->options($eventOptions)
                                    ->required()
                                    ->native(false),
                                Forms\Components\TextInput::make('mail_subject')
                                    ->label('Email Subject')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('mail_body')
                                    ->label('Email Body')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('in_app_title')
                                    ->label('In-App / Push Title'),
                                Forms\Components\Textarea::make('in_app_body')
                                    ->label('In-App / Push Body')
                                    ->rows(2),
                                Forms\Components\Textarea::make('sms_text')
                                    ->label('SMS Text')
                                    ->rows(2),
                                Forms\Components\Textarea::make('whatsapp_text')
                                    ->label('WhatsApp Text')
                                    ->rows(2),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add template'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = app(NotificationSettingsStore::class);

        foreach ($data as $key => $value) {
            if (property_exists($store, $key)) {
                $store->{$key} = $value;
            }
        }

        try {
            $store->save();
        } catch (MissingSettings) {
            $this->ensureSettingsExist($store);
            $store->refresh();

            foreach ($data as $key => $value) {
                if (property_exists($store, $key)) {
                    $store->{$key} = $value;
                }
            }

            $store->save();
        }

        FilamentNotification::make()
            ->title('Notification settings saved')
            ->success()
            ->send();
    }

    private function ensureSettingsExist(NotificationSettingsStore $store): void
    {
        $group = $store::group();
        $ref = new \ReflectionClass($store);
        $properties = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            $exists = SettingsProperty::query()
                ->where('group', $group)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            SettingsProperty::query()->create([
                'group' => $group,
                'name' => $name,
                'payload' => json_encode($store->{$name}),
                'locked' => false,
            ]);
        }
    }
}
