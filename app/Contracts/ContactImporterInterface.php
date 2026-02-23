<?php

namespace App\Contracts;

use Generator;

/**
 * Interface for importing contacts from files.
 */
interface ContactImporterInterface
{
    /**
     * Checks if the given MIME type is supported.
     */
    public function supports(string $mimeType): bool;

    /**
     * Parses the file and returns contacts one by one.
     *
     * @param  string  $filePath  Absolute path to the file
     * @return Generator<int, array{email: string, first_name: string, last_name: string}>
     */
    public function parse(string $filePath): Generator;
}
