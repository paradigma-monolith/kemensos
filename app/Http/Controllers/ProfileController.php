<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile edit form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin') || $user->can('manage users');
        $scope = $request->query('scope', $isSuperAdmin ? 'all' : 'me');

        if ($isSuperAdmin && $scope === 'all') {
            $loginHistories = LoginHistory::with('user')
                ->latest('login_at')
                ->take(50)
                ->get();
        } else {
            $loginHistories = $user->loginHistories()
                ->latest('login_at')
                ->take(30)
                ->get();
        }

        return Inertia::render('Profile/Edit', [
            'status' => session('status'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'is_super_admin' => $isSuperAdmin,
            ],
            'login_histories' => $loginHistories,
            'current_ip' => $request->ip() ?? '127.0.0.1',
            'scope' => $scope,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }

    /**
     * Upload and update user avatar image.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ], [
            'avatar.required' => 'Pilih file gambar untuk avatar.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        $user = $request->user();

        // Delete previous avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar in public/avatars directory
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return back()->with('status', 'Foto avatar berhasil diperbarui.');
    }
}
