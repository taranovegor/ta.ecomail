<?php

namespace App\Http\ViewModels;

use App\Models\ImportIssue;
use Carbon\CarbonInterface;
use Stringable;

class ImportIssueViewModel implements Stringable
{
    public function __construct(
        public ImportIssue $issue,
    ) {}

    public function id(): int
    {
        return $this->issue->id;
    }

    public function importId(): int
    {
        return $this->issue->import_id;
    }

    public function type(): string
    {
        return $this->issue->type->value;
    }

    public function email(): string
    {
        return $this->issue->email;
    }

    public function firstName(): ?string
    {
        return $this->issue->first_name;
    }

    public function lastName(): ?string
    {
        return $this->issue->last_name;
    }

    public function fullName(): string
    {
        return trim("{$this->issue->first_name} {$this->issue->last_name}");
    }

    public function reason(): string
    {
        return $this->issue->reason;
    }

    public function createdAt(): ?CarbonInterface
    {
        return $this->issue->created_at;
    }

    public function __toString(): string
    {
        return (string) $this->issue->id;
    }
}
