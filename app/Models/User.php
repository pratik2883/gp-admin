<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'role',
        'role_subtype',
        'status',
        'is_super_admin',
        'notification_preferences',
        'terms_accepted_at',
        'notification_consent_granted_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'notification_preferences' => 'array',
            'terms_accepted_at' => 'datetime',
            'notification_consent_granted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            if (! $user->isDirty('status') || $user->role !== 'gp') {
                return;
            }

            $gp = $user->gp;
            if (! $gp) {
                return;
            }

            $gpStatus = $user->status === 'active' ? 'approved' : $user->status;
            if ($gp->status !== $gpStatus) {
                $gp->status = $gpStatus;
                $gp->save();
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeGps($query)
    {
        return $query->where('role', 'gp');
    }

    public function scopeSpecialists($query)
    {
        return $query->where('role', 'specialist');
    }

    public function gp(): HasOne
    {
        return $this->hasOne(Gp::class);
    }

    public function specialist(): HasOne
    {
        return $this->hasOne(Specialist::class);
    }

    public function diagnosticCenter(): HasOne
    {
        return $this->hasOne(DiagnosticCenter::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class)->latest('id');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_admin_id');
    }

    public function supportTicketMessages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'sender_id');
    }

    public function isGp(): bool
    {
        return $this->role === 'gp';
    }

    public function isSpecialist(): bool
    {
        return $this->role === 'specialist';
    }

    public function isHospital(): bool
    {
        return $this->role === 'specialist' && $this->role_subtype === 'hospital';
    }

    public function isDiagnosticCenter(): bool
    {
        return $this->role === 'specialist' && $this->role_subtype === 'diagnostic_center';
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function resolvedNotificationPreferences(): array
    {
        $stored = is_array($this->notification_preferences) ? $this->notification_preferences : [];

        return [
            'push' => array_key_exists('push', $stored) ? (bool) $stored['push'] : true,
            'email' => array_key_exists('email', $stored) ? (bool) $stored['email'] : true,
            'sms' => array_key_exists('sms', $stored) ? (bool) $stored['sms'] : true,
            'whatsapp' => array_key_exists('whatsapp', $stored) ? (bool) $stored['whatsapp'] : true,
        ];
    }

    public function allowsNotificationChannel(string $channel): bool
    {
        $prefs = $this->resolvedNotificationPreferences();

        return match ($channel) {
            'push' => $prefs['push'],
            'email' => $prefs['email'],
            'sms' => $prefs['sms'],
            'whatsapp' => $prefs['whatsapp'],
            default => true,
        };
    }

    public function routeNotificationForTwilioSms($notification = null): ?string
    {
        return $this->mobile ?: null;
    }

    public function routeNotificationForTwilioWhatsApp($notification = null): ?string
    {
        return $this->mobile ?: null;
    }
}
