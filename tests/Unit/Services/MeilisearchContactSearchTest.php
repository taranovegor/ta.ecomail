<?php

use App\Contracts\ContactSearchInterface;
use App\Search\MeilisearchContactSearch;

test('implements ContactSearchInterface', function () {
    $search = new MeilisearchContactSearch;

    expect($search)->toBeInstanceOf(ContactSearchInterface::class);
});
