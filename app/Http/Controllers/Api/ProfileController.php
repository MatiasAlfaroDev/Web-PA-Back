<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Progress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->withStats($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $request->user()->update($data);

        return response()->json($this->withStats($request->user()->fresh()));
    }

    // One avatar per user — each upload replaces the previous one.
    // ponytail: the old file is left orphaned in the bucket rather than
    // deleted; storage cost is trivial at this scale, add cleanup if it isn't.
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:4096'],
        ]);

        $path = $request->file('avatar')->store('avatars', 's3');
        $url = Storage::disk('s3')->url($path);

        $request->user()->update(['avatar_url' => $url]);

        return response()->json($this->withStats($request->user()->fresh()));
    }

    private function withStats(User $user): User
    {
        $user->setAttribute('points', Progress::points($user));
        $user->setAttribute('streak', Progress::streak($user));
        $user->setAttribute('solved', Progress::solvedCount($user));

        return $user;
    }
}
