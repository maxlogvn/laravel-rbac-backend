<?php

use Tests\TestCase;

it('base controller has swagger info annotation', function () {
    $reflection = new ReflectionClass(App\Http\Controllers\Controller::class);
    $comment = $reflection->getDocComment();

    expect($comment)->not->toBeFalse()
        ->and($comment)->toContain('@OA\Info')
        ->and($comment)->toContain('@OA\SecurityScheme');
});
