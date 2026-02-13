<?php

use Tests\TestCase;

it('hello controller has swagger documentation', function () {
    $reflection = new ReflectionClass(App\Http\Controllers\HelloController::class);
    $method = $reflection->getMethod('__invoke');
    $comment = $method->getDocComment();

    expect($comment)->not->toBeFalse()
        ->and($comment)->toContain('@OA\Get')
        ->and($comment)->toContain('/api/hello');
});

it('hello endpoint returns documented response', function () {
    $this->getJson('/api/hello')
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Hello World',
            'status' => 'success',
        ]);
});
