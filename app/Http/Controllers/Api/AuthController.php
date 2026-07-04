<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Rules\CedulaUruguaya;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'ci' => ['required', 'string', new CedulaUruguaya, 'unique:users,ci'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $data['ci'] = preg_replace('/[.\-\s]/', '', $data['ci']);

        $user = User::create($data);
        $this->sendVerificationCode($user);

        return response()->json([
            'message' => 'Registro exitoso. Te enviamos un código de verificación por email.',
        ], 201);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || $user->verification_code !== $data['code'] || $user->verification_code_expires_at?->isPast()) {
            return response()->json(['message' => 'Código inválido o expirado.'], 422);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();

        return response()->json(['message' => 'Email verificado. Ya podés iniciar sesión.']);
    }

    public function resendCode(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $data['email'])->whereNull('email_verified_at')->first();

        if ($user) {
            $this->sendVerificationCode($user);
        }

        // Same response whether or not the email exists (no user enumeration).
        return response()->json(['message' => 'Si el email existe y no está verificado, enviamos un nuevo código.']);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::once($data)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        $user = Auth::user();

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Si el email existe, enviamos un enlace para cambiar la contraseña.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete();
        });

        if ($status !== Password::PasswordReset) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Contraseña actualizada.']);
    }

    private function sendVerificationCode(User $user): void
    {
        $user->forceFill([
            'verification_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        Mail::to($user)->send(new VerificationCodeMail($user));
    }
}
