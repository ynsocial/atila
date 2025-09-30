<?php

return [
    'sentence_min' => 3,
    'name_cooldown_days' => 30,
    'rate_per_minute' => 1,
    'sqli_patterns' => [
        'select\s*\(',
        'sleep\s*\(',
        'union\s+select',
        'or\s+1=1',
        'information_schema',
        '\\-\\-', '/\\*', '\\*/', '`', '<script', '<\\?php', '\\$\\{'
    ],
];

