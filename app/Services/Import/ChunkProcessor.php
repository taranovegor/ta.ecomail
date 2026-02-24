<?php

namespace App\Services\Import;

use App\Enums\IssueType;
use App\Models\Contact;
use App\Models\ImportIssue;
use App\Rules\ContactRules;
use App\Services\EmailNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ChunkProcessor
{
    protected ?CarbonInterface $startedAt = null;

    /**
     * @return array{total: int, imported: int, duplicates: int, invalid: int}
     */
    public function process(Filesystem $fs, string $filePath, int $importId, int $batchSize): array
    {
        $this->startedAt = now();

        $stream = $fs->readStream($filePath);

        if ($stream === null) {
            throw new RuntimeException("Cannot read shard: {$filePath}");
        }

        $counters = ['total' => 0, 'imported' => 0, 'duplicates' => 0, 'invalid' => 0];
        $seenEmails = [];
        $insertBuffer = [];
        $issueBuffer = [];

        try {
            fgetcsv($stream);

            while (($row = fgetcsv($stream)) !== false) {
                if (count($row) < 3) {
                    continue;
                }

                $counters['total']++;

                $record = [
                    'email' => EmailNormalizer::normalize($row[0]),
                    'first_name' => $row[1],
                    'last_name' => $row[2],
                ];

                $validationError = $this->validate($record);
                if ($validationError !== null) {
                    $counters['invalid']++;
                    $issueBuffer[] = $this->buildIssue($importId, IssueType::Invalid, $record, $validationError);
                    $this->flushIssuesIfNeeded($issueBuffer, $batchSize);

                    continue;
                }

                if (isset($seenEmails[$record['email']])) {
                    $counters['duplicates']++;
                    $issueBuffer[] = $this->buildIssue($importId, IssueType::Duplicate, $record, 'Duplicate email within file');
                    $this->flushIssuesIfNeeded($issueBuffer, $batchSize);

                    continue;
                }

                $seenEmails[$record['email']] = true;
                $insertBuffer[] = $record;

                if (count($insertBuffer) >= $batchSize) {
                    $counters['imported'] += $this->flushInserts($insertBuffer);
                }
            }

            if ($insertBuffer !== []) {
                $counters['imported'] += $this->flushInserts($insertBuffer);
            }

            if ($issueBuffer !== []) {
                ImportIssue::insert($issueBuffer);
            }
        } finally {
            fclose($stream);
        }

        return $counters;
    }

    private function validate(array $record): ?string
    {
        // Laravel Validator is too slow for this case.
        // Tests showed that replacing Validator::make with simple native PHP checks
        // makes the process about 3 times faster and uses 15% less memory.
        //
        // We use filter_var and strlen for basic validation,
        // BUT, the email check is not exactly the same as Laravel 'email:rfc' rule.
        //
        // This is a topic for discussion: what is matters more to us.
        $validator = Validator::make($record, ContactRules::base());

        if ($validator->fails()) {
            return implode('; ', $validator->errors()->all());
        }

        return null;
    }

    /**
     * @see ImportIssue::$fillable
     */
    private function buildIssue(int $importId, IssueType $type, array $record, string $reason): array
    {
        return [
            'import_id' => $importId,
            'type' => $type->value,
            'email' => mb_substr($record['email'], 0, 255),
            'first_name' => mb_substr($record['first_name'] ?? '', 0, 255) ?: null,
            'last_name' => mb_substr($record['last_name'] ?? '', 0, 255) ?: null,
            'reason' => mb_substr($reason, 0, 500),
            'created_at' => $this->startedAt,
        ];
    }

    /**
     * @see Contact::$fillable
     *
     * @return int Number of actually inserted rows
     */
    private function flushInserts(array &$buffer): int
    {
        $rows = array_map(fn (array $r) => [
            'email' => $r['email'],
            'first_name' => $r['first_name'],
            'last_name' => $r['last_name'],
            'created_at' => $this->startedAt,
            'updated_at' => $this->startedAt,
        ], $buffer);

        $affected = DB::table('contacts')->insertOrIgnore($rows);
        $buffer = [];

        return $affected;
    }

    private function flushIssuesIfNeeded(array &$buffer, int $batchSize): void
    {
        if (count($buffer) >= $batchSize) {
            ImportIssue::insert($buffer);
            $buffer = [];
        }
    }
}
