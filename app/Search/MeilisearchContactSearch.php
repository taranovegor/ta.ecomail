<?php

namespace App\Search;

use App\Contracts\ContactSearchInterface;
use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MeilisearchContactSearch implements ContactSearchInterface
{
    /**
     * {@inheritDoc}
     */
    public function search(string $query, int $perPage = 10): LengthAwarePaginator
    {
        return Contact::search($query)
            ->paginate($perPage);
    }
}
