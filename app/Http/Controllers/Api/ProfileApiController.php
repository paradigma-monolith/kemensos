<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileApiController extends Controller
{
    /**
     * Get authenticated user profile details with roles & permissions.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /**
     * Update profile details.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'user' => $request->user(),
        ]);
    }

    /**
     * Update password via API.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kata sandi berhasil diubah.',
        ]);
    }

    /**
     * Upload avatar image via API.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'Avatar berhasil diperbarui.',
            'avatar_url' => $user->avatar_url,
        ]);
    }

    /**
     * Get user login history logs with IP, browser, and device info.
     * Super Admin can query all users with scope=all.
     */
    public function loginHistories(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin') || $user->can('manage users');
        $scope = $request->query('scope', $isSuperAdmin ? 'all' : 'me');

        $query = ($isSuperAdmin && $scope === 'all')
            ? \App\Models\LoginHistory::with('user')
            : $user->loginHistories();

        $histories = $query->latest('login_at')->paginate(20);

        return response()->json([
            'status' => 'success',
            'current_ip' => $request->ip() ?? '127.0.0.1',
            'scope' => $scope,
            'is_super_admin' => $isSuperAdmin,
            'histories' => $histories,
        ]);
    }
}
