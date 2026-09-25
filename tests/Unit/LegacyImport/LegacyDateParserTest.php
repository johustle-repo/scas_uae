<?php

use App\Support\LegacyImport\LegacyDateParser;

test('parses a plain DD.MM.YYYY date', function () {
    $result = LegacyDateParser::parse('30.10.2024');

    expect($result->date?->toDateString())->toBe('2024-10-30')
        ->and($result->isApproximate)->toBeFalse()
        ->and($result->annotation)->toBeNull();
});

test('parses the corrupted month.year decimal as an approximate date', function () {
    $result = LegacyDateParser::parse('8.2018000000000004');

    expect($result->date?->toDateString())->toBe('2018-08-01')
        ->and($result->isApproximate)->toBeTrue()
        ->and($result->annotation)->toBeNull();
});

test('parses a clean month.year value the same way', function () {
    $result = LegacyDateParser::parse('12.2024');

    expect($result->date?->toDateString())->toBe('2024-12-01')
        ->and($result->isApproximate)->toBeTrue();
});

test('preserves a "(Valid Until)" annotation without guessing a structured date', function () {
    $result = LegacyDateParser::parse('(Valid Until) 11.12.2026');

    expect($result->date)->toBeNull()
        ->and($result->isApproximate)->toBeFalse()
        ->and($result->annotation)->toBe('(Valid Until) 11.12.2026');
});

test('preserves an "(ED)" annotation without guessing a structured date', function () {
    $result = LegacyDateParser::parse('(ED) 30.08.2029');

    expect($result->date)->toBeNull()
        ->and($result->annotation)->toBe('(ED) 30.08.2029');
});

test('preserves a trailing "(ED: ...)" annotation without guessing a structured date', function () {
    $result = LegacyDateParser::parse('11.12.2024 (ED: 30.08.2029)');

    expect($result->date)->toBeNull()
        ->and($result->annotation)->toBe('11.12.2024 (ED: 30.08.2029)');
});

test('returns a blank result for null or empty input', function (?string $input) {
    $result = LegacyDateParser::parse($input);

    expect($result->date)->toBeNull()
        ->and($result->isApproximate)->toBeFalse()
        ->and($result->annotation)->toBeNull();
})->with([null, '', '   ']);
