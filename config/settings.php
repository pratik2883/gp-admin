<?php

use App\Settings\GeneralSettings;
use App\Settings\NotificationSettings;
use App\Settings\OtpSettings;
use App\Settings\WorkflowSettings;

return [
    'settings' => [
        GeneralSettings::class,
        WorkflowSettings::class,
        NotificationSettings::class,
        OtpSettings::class,
    ],
];
