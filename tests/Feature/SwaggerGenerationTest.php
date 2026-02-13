<?php

use Tests\TestCase;

it('generates openapi json file', function () {
    expect(storage_path('api-docs/api-docs.json'))->toBeFile();
});

it('openapi json contains valid structure', function () {
    $json = json_decode(file_get_contents(storage_path('api-docs/api-docs.json')), true);

    expect($json)->toHaveKey('openapi')
        ->and($json)->toHaveKey('info')
        ->and($json)->toHaveKey('paths')
        ->and($json['openapi'])->toBe('3.0.0');
});

it('openapi json contains hello endpoint', function () {
    $json = json_decode(file_get_contents(storage_path('api-docs/api-docs.json')), true);

    expect($json['paths'])->toHaveKey('/api/hello');
});
