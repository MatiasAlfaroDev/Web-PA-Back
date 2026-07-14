<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteLock;
use App\Support\Progress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SiteLockController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['locked_until' => SiteLock::activeUntil()?->toJSON()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locked_until' => ['required_without:duration_minutes', 'date'],
            'duration_minutes' => ['required_without:locked_until', 'integer', 'min:1'],
        ]);

        // datetime-local inputs carry no timezone — the value is the teacher's
        // local wall-clock time (Uruguay), not the app's default timezone.
        $until = isset($data['locked_until'])
            ? Carbon::parse($data['locked_until'], Progress::TZ)
            : now()->addMinutes($data['duration_minutes']);

        abort_if($until->isPast(), 422, 'La fecha debe ser posterior a ahora.');

        SiteLock::set($until);

        return response()->json(['locked_until' => $until->toJSON()]);
    }

    public function destroy(): Response
    {
        SiteLock::clear();

        return response()->noContent();
    }
}
