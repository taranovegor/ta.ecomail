<?php

namespace App\Services\Import;

use App\Contracts\ContactImporterInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;

class ShardWriter
{
    private int $totalRecords = 0;

    /**
     * @param  int  $shardCount  Number of shards to create
     * @return int Total records written across all shards
     */
    public function write(ContactImporterInterface $importer, Filesystem $fs, string $sourceFilePath, string $shardDir, int $shardCount): int
    {
        $sourceTempPath = $this->downloadToTempFile($fs, $sourceFilePath);
        $handles = $this->openTempHandles($shardCount);

        try {
            foreach ($importer->parse($sourceTempPath) as $record) {
                $index = $this->shardIndex($record['email'], $shardCount);

                fputcsv($handles[$index], [
                    $record['email'],
                    $record['first_name'],
                    $record['last_name'],
                ]);

                $this->totalRecords++;
            }
        } finally {
            @unlink($sourceTempPath);
        }

        for ($i = 0; $i < $shardCount; $i++) {
            rewind($handles[$i]);
            $fs->writeStream("{$shardDir}/shard_{$i}.csv", $handles[$i]);
            fclose($handles[$i]);
        }

        return $this->totalRecords;
    }

    private function shardIndex(string $email, int $shardCount): int
    {
        $index = crc32(strtolower($email)) % $shardCount;

        return $index < 0 ? $index + $shardCount : $index;
    }

    /**
     * @return resource[]
     */
    private function openTempHandles(int $shardCount): array
    {
        $handles = [];

        for ($i = 0; $i < $shardCount; $i++) {
            $handle = tmpfile();
            if ($handle === false) {
                throw new RuntimeException('Failed to create temporary file');
            }
            fputcsv($handle, ['email', 'first_name', 'last_name']);
            $handles[$i] = $handle;
        }

        return $handles;
    }

    private function downloadToTempFile(Filesystem $fs, string $path): string
    {
        $stream = $fs->readStream($path);

        if ($stream === null) {
            throw new RuntimeException("Cannot read file: {$path}");
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'import_');

        try {
            $tempHandle = fopen($tempPath, 'w');
            stream_copy_to_stream($stream, $tempHandle);
            fclose($tempHandle);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $tempPath;
    }
}
