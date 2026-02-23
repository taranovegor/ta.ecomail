<?php

namespace App\Services\Import;

use App\Contracts\ContactImporterInterface;
use DOMNode;
use Generator;
use RuntimeException;
use XMLReader;

/**
 * XML contact importer that parses contacts from a file.
 *
 * Expected XML format:
 * <data>
 *   <item>
 *     <email>...</email>
 *     <first_name>...</first_name>
 *     <last_name>...</last_name>
 *   </item>
 * </data>
 */
final readonly class XmlContactImporter implements ContactImporterInterface
{
    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, ['text/xml', 'application/xml'], true);
    }

    /**
     * @return Generator<array{email: string, first_name: string, last_name: string}>
     *
     * @throws RuntimeException If the file cannot be opened.
     */
    public function parse(string $filePath): Generator
    {
        $reader = new XMLReader;

        if (! @$reader->open($filePath, encoding: 'UTF-8')) {
            throw new RuntimeException("Cannot open XML file: {$filePath}");
        }

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'item') {
                    continue;
                }

                $node = $reader->expand();

                if ($node === false) {
                    continue;
                }

                yield [
                    'email' => $this->getChildValue($node, 'email'),
                    'first_name' => $this->getChildValue($node, 'first_name'),
                    'last_name' => $this->getChildValue($node, 'last_name'),
                ];
            }
        } finally {
            $reader->close();
        }
    }

    private function getChildValue(DOMNode $node, string $childName): string
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeName === $childName) {
                return trim($child->textContent);
            }
        }

        return '';
    }
}
