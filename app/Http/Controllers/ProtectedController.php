<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Protected",
 *     description="Protected endpoints requiring authentication"
 * )
 */
class ProtectedController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/protected",
     *     summary="Get protected data",
     *     description="Requires valid JWT token",
     *     tags={"Protected"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function __invoke(Request $request): array
    {
        return [
            'message' => 'Protected data',
            'user_id' => $request->user()->id,
        ];
    }
}
