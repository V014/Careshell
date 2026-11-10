<?php
declare(strict_types=1);

return [
    'host' => getenv('MEDCRM_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('MEDCRM_DB_PORT') ?: '3306',
    'database' => getenv('MEDCRM_DB_NAME') ?: 'careshell',
    'username' => getenv('MEDCRM_DB_USER') ?: 'root',
    'password' => getenv('MEDCRM_DB_PASSWORD') ?: '',
];
