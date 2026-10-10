<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filament's header bell only lists notifications whose payload contains
     * `format => 'filament'` — see
     * Filament\Notifications\Livewire\DatabaseNotifications::getNotificationsQuery().
     *
     * app/Notifications/Concerns/RendersFromTemplates::toArray() sets that key, but
     * rows written before it was introduced are invisible to the bell while still
     * showing up in any unfiltered list (such as the dashboard widget).
     * Backfill the key so both views agree.
     */
    public function up(): void
    {
        $this->eachNotification(function (object $row): ?array {
            $data = json_decode((string) $row->data, true);

            if (! is_array($data) || array_key_exists('format', $data)) {
                return null;
            }

            $data['format'] = 'filament';

            return $data;
        });
    }

    /**
     * Remove the key again, but only from payloads shaped like the ones this
     * migration wrote: `format => 'filament'` with no `duration` key. The
     * application always writes `duration` alongside `format`, so notifications
     * it sent are left untouched.
     */
    public function down(): void
    {
        $this->eachNotification(function (object $row): ?array {
            $data = json_decode((string) $row->data, true);

            if (! is_array($data)
                || ($data['format'] ?? null) !== 'filament'
                || array_key_exists('duration', $data)) {
                return null;
            }

            unset($data['format']);

            return $data;
        });
    }

    /**
     * Walk every notification, letting $mutate return replacement payload data
     * (or null to leave the row alone). Decoding in PHP keeps this portable
     * across the JSON path dialects of MySQL and SQLite.
     */
    private function eachNotification(callable $mutate): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->select('id', 'data')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($mutate): void {
                foreach ($rows as $row) {
                    $data = $mutate($row);

                    if ($data === null) {
                        continue;
                    }

                    DB::table('notifications')
                        ->where('id', $row->id)
                        ->update(['data' => json_encode($data)]);
                }
            });
    }
};
