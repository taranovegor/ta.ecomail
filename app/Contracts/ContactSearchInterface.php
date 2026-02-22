<?php

namespace App\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ContactSearchInterface
{
    /**
     * Full-text search across contacts.
     *
     * @param  string  $query  Search query (name, surname or email)
     * @param  int  $perPage  Number of results per page
     * @return LengthAwarePaginator Paginated results
     */
    public function search(string $query, int $perPage = 10): LengthAwarePaginator;
}
