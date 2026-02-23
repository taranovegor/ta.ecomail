<?php

namespace App\Enums;

enum IssueType: string
{
    case Duplicate = 'duplicate';
    case Invalid = 'invalid';
}
