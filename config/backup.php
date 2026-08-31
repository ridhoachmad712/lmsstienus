<?php

return [
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    // Opsional: direktori kedua yang berada di volume/akun backup terpisah.
    'copy_path' => env('BACKUP_COPY_PATH'),
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
];
