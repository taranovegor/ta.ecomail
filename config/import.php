<?php

return [
    'shard_size' => env('IMPORT_SHARD_SIZE', 5 * 1024 * 1024), // 5MB
    'batch_size' => env('IMPORT_BATCH_SIZE', 1000),
    'max_file_size_kb' => env('IMPORT_MAX_FILE_SIZE_KB', 50 * 1024), // 50MB
    'disk' => env('IMPORT_DISK', 'local'),
];
