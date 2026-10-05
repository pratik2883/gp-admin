<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Models\SettingsProperty;

return new class extends Migration
{
    public function up(): void
    {
        $group = 'notifications';
        $missing = [
            'message_central_sms_template_id' => null,
            'message_central_sms_entity_id' => null,
        ];

        foreach ($missing as $name => $payload) {
            SettingsProperty::query()->updateOrCreate(
                ['group' => $group, 'name' => $name],
                ['payload' => json_encode($payload), 'locked' => false]
            );
        }
    }

    public function down(): void
    {
        SettingsProperty::query()
            ->where('group', 'notifications')
            ->whereIn('name', ['message_central_sms_template_id', 'message_central_sms_entity_id'])
            ->delete();
    }
};