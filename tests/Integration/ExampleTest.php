<?php

test('database connection is working', function () {
    expect(DB::connection()->getPdo())->toBeObject();
});
