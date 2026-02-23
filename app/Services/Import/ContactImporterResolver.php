<?php

namespace App\Services\Import;

use App\Contracts\ContactImporterInterface;
use App\Exceptions\UnsupportedFileFormatException;

/**
 * Resolves the appropriate contact importer based on a file's MIME type.
 */
final readonly class ContactImporterResolver
{
    /**
     * @param  ContactImporterInterface[]  $importers
     */
    public function __construct(
        private array $importers,
    ) {}

    /**
     * @throws UnsupportedFileFormatException If no importer supports the MIME type.
     */
    public function resolve(string $mimeType): ContactImporterInterface
    {
        foreach ($this->importers as $importer) {
            if ($importer->supports($mimeType)) {
                return $importer;
            }
        }

        throw new UnsupportedFileFormatException(
            "No importer found for MIME type: {$mimeType}"
        );
    }

    public function resolvable(string $mimeType): bool
    {
        try {
            $this->resolve($mimeType);

            return true;
        } catch (UnsupportedFileFormatException) {
            return false;
        }
    }
}
