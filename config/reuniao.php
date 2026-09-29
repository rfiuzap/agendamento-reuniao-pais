<?php

return [
    // Shown in the footer; bumped on every delivered improvement.
    'version' => '1.15',

    // Parent access code (sent by e-mail)
    'code_ttl_minutes' => (int) env('AUTH_CODE_TTL_MINUTES', 10),
    'code_max_attempts' => (int) env('AUTH_CODE_MAX_ATTEMPTS', 5),
    'code_resend_seconds' => (int) env('AUTH_CODE_RESEND_SECONDS', 60),
    'code_max_per_hour' => (int) env('AUTH_CODE_MAX_PER_HOUR', 8),

    // Minutes a parent session stays valid after entering the code
    'parent_session_minutes' => (int) env('PARENT_SESSION_MINUTES', 120),
];
