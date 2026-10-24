<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Profile Controller
 *
 * This controller handles the management of the admin profile.
 *
 * @package App\Http\Controllers\Admin
 */
class ProfileController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService) {}

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('admin.profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:10240'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:10240'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $photoFile = $request->file('profile_photo') ?? $request->file('avatar');
        if ($photoFile) {
            $newPhotoPath = $this->fileUploadService->uploadProfilePhoto(
                $photoFile,
                'admins/photos'
            );

            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->profile_photo = $newPhotoPath;
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->tokens()->delete();
        }

        $user->save();

        ToastMagic::success('Profile updated successfully.');

        return redirect()->route('admin.profile.edit');
    }
}
