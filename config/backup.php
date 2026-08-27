<?php

return [
    'recipient' => env('DATABASE_BACKUP_EMAIL', 'enockmambou@gmail.com'),
    'directory' => env('DATABASE_BACKUP_DIRECTORY', storage_path('app/private/database-backups')),
    'attachment_max_mb' => (int) env('DATABASE_BACKUP_ATTACHMENT_MAX_MB', 15),
    'link_expiration_hours' => (int) env('DATABASE_BACKUP_LINK_EXPIRATION_HOURS', 168),
    'retention_days' => (int) env('DATABASE_BACKUP_RETENTION_DAYS', 7),
    'binaries' => [
        'mysqldump' => env('MYSQLDUMP_BINARY', 'mysqldump'),
        'pg_dump' => env('PG_DUMP_BINARY', 'pg_dump'),
    ],
];
