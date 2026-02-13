<?php

namespace App\Http\Controllers;

class HelloController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/hello",
     *     summary="Get hello message",
     *     description="Returns a friendly greeting message",
     *     @OA\Response(
     *         response=200,
     *     description="Successful response",
     *     @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Hello World"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             )
     *         )
     *     )
     * )
     */
    public function __invoke(): array
    {
        return [
            'message' => 'Hello World',
            'status' => 'success',
        ];
    }
}
