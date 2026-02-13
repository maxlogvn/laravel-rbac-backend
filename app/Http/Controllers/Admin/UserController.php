<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(15);

        return response()->json($users);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        // Assign default role
        $role = $request->input('role', 'member');
        $user->assignRole($role);

        return response()->json($user->load('roles'), 201);
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $user->load('roles'),
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(null, 204);
    }
}
