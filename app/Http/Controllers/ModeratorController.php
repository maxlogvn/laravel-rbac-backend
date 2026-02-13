<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Moderation",
 *     description="API moderation endpoints for moderators and admins"
 * )
 */
class ModeratorController extends Controller
{
    /**
     * Lock a user account.
     *
     * @OA\Post(
     *     path="/api/moderation/users/{user}/lock",
     *     summary="Lock a user account (Moderator+)",
     *     tags={"Moderation"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"reason"},
     *
     *                 @OA\Property(
     *                     property="reason",
     *                     type="string",
     *                     example="Violation of community guidelines"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User locked successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="is_locked", type="boolean"),
     *                 @OA\Property(property="locked_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Cannot lock admin users or insufficient permissions"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function lockUser(Request $request, User $user)
    {
        // Prevent locking admin users
        if ($user->hasRole('admin')) {
            return response()->json([
                'message' => 'Cannot lock admin users',
                'error' => 'cannot_lock_admin',
            ], 403);
        }

        // Prevent self-locking
        if ($user->id === auth('api')->id()) {
            return response()->json([
                'message' => 'Cannot lock yourself',
                'error' => 'cannot_lock_self',
            ], 403);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // Check if is_locked column exists in database
        $columns = \Schema::getColumnListing('users');
        if (! in_array('is_locked', $columns)) {
            return response()->json([
                'message' => 'User lock feature requires database migration for is_locked column',
                'error' => 'feature_not_implemented',
            ], 501);
        }

        $user->update([
            'is_locked' => true,
            'locked_at' => now(),
            'lock_reason' => $request->reason,
        ]);

        $user->refresh();

        return response()->json([
            'message' => 'User locked successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_locked' => $user->is_locked,
                'locked_at' => $user->locked_at,
            ],
        ]);
    }

    /**
     * Unlock a user account.
     *
     * @OA\Post(
     *     path="/api/moderation/users/{user}/unlock",
     *     summary="Unlock a user account (Moderator+)",
     *     tags={"Moderation"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User unlocked successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="is_locked", type="boolean")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function unlockUser(Request $request, User $user)
    {
        // Check if is_locked column exists in database
        $columns = \Schema::getColumnListing('users');
        if (! in_array('is_locked', $columns)) {
            return response()->json([
                'message' => 'User unlock feature requires database migration for is_locked column',
                'error' => 'feature_not_implemented',
            ], 501);
        }

        $user->update([
            'is_locked' => false,
            'locked_at' => null,
            'lock_reason' => null,
        ]);

        $user->refresh();

        return response()->json([
            'message' => 'User unlocked successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_locked' => $user->is_locked,
            ],
        ]);
    }

    /**
     * Get list of locked users.
     *
     * @OA\Get(
     *     path="/api/moderation/users/locked",
     *     summary="Get list of locked users (Moderator+)",
     *     tags={"Moderation"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of locked users",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="locked_at", type="string", format="date-time"),
     *                 @OA\Property(property="lock_reason", type="string")
     *             )),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function lockedUsers(Request $request)
    {
        // Check if is_locked column exists
        $columns = \Schema::getColumnListing('users');
        if (! in_array('is_locked', $columns)) {
            return response()->json([
                'message' => 'User lock feature requires database migration for is_locked column',
                'error' => 'feature_not_implemented',
            ], 501);
        }

        $perPage = $request->input('per_page', 15);

        $users = User::where('is_locked', true)
            ->with('roles')
            ->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Approve a post (placeholder for future post moderation).
     *
     * @OA\Post(
     *     path="/api/moderation/posts/{postId}/approve",
     *     summary="Approve a post (Moderator+)",
     *     tags={"Moderation"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Post approved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=501,
     *         description="Post moderation not implemented yet"
     *     )
     * )
     */
    public function approvePost(Request $request, int $postId)
    {
        // Placeholder for future post moderation functionality
        return response()->json([
            'message' => 'Post moderation feature not implemented yet',
            'error' => 'feature_not_implemented',
            'post_id' => $postId,
        ], 501);
    }

    /**
     * Reject/delete a post (placeholder for future post moderation).
     *
     * @OA\Delete(
     *     path="/api/moderation/posts/{postId}",
     *     summary="Delete a post (Moderator+)",
     *     tags={"Moderation"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Post deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=501,
     *         description="Post moderation not implemented yet"
     *     )
     * )
     */
    public function deletePost(Request $request, int $postId)
    {
        // Placeholder for future post moderation functionality
        return response()->json([
            'message' => 'Post moderation feature not implemented yet',
            'error' => 'feature_not_implemented',
            'post_id' => $postId,
        ], 501);
    }
}
