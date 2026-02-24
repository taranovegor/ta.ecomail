<?php

use App\Services\EmailNormalizer;

test('lowercases email', function () {
    expect(EmailNormalizer::normalize('USER@EXAMPLE.COM'))->toBe('user@example.com');
});

test('trims whitespace', function () {
    expect(EmailNormalizer::normalize('  user@example.com  '))->toBe('user@example.com');
});

test('handles both at once', function () {
    expect(EmailNormalizer::normalize('  USER@EXAMPLE.COM  '))->toBe('user@example.com');
});

test('leaves normalized email unchanged', function () {
    expect(EmailNormalizer::normalize('user@example.com'))->toBe('user@example.com');
});
