<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Progress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    private function withStats(User $user): User
    {
        $user->setAttribute('points', Progress::points($user));
        $user->setAttribute('streak', Progress::streak($user));
        $user->setAttribute('solved', Progress::solvedCount($user));

        return $user;
    }
}
