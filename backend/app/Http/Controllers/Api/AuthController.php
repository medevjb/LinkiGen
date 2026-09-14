<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * identity/account-access
 *
 * Session cookie auth via Sanctum's stateful SPA guard (08-api-contract.md §2).
 * Error codes reference docs/16-error-catalog.md §2-3.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $key = 'register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->rateLimited($key);
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        // Generic response regardless of whether the email already exists — no account enumeration.
        $existing = User::where('email', $data['email'])->first();

        if (! $existing) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'timezone' => $request->header('X-Timezone', 'UTC'),
            ]);

            event(new Registered($user));
        }
        // else: silently no-op. A real deployment sends a "you already have an account" email here.

        return response()->json([
            'message' => 'We sent a 6-digit verification code to your email.',
        ], 202);
    }

    /**
     * Verifies the 6-digit code sent on registration/resend. Unlike a link
     * click, this is an XHR from the frontend SPA carrying the Origin header
     * Sanctum needs — so, on success, we can establish a real session here
     * and skip a separate sign-in step.
     */
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return $this->error('OTP_INVALID', 'That code is incorrect.', 422);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.', 'already_verified' => true]);
        }

        $otp = $user->emailVerificationOtp;

        if (! $otp) {
            return $this->error('OTP_EXPIRED', 'This code has expired. Request a new one.', 410);
        }

        if ($otp->isExpired()) {
            $otp->delete();

            return $this->error('OTP_EXPIRED', 'This code has expired. Request a new one.', 410);
        }

        if ($otp->attempts >= 5) {
            return $this->error('OTP_ATTEMPTS_EXCEEDED', 'Too many incorrect attempts. Request a new code.', 429);
        }

        if (! $otp->matches($data['code'])) {
            $otp->increment('attempts');

            return $this->error('OTP_INVALID', 'That code is incorrect.', 422);
        }

        $user->markEmailAsVerified();
        $user->onboarding_status = 'profile_import';
        $user->save();
        $otp->delete();

        // The now-deleted OTP row is still cached on this model instance's
        // relation — drop it so it can never be serialized into a response,
        // defense in depth alongside EmailVerificationOtp's #[Hidden] on
        // code_hash.
        $user->unsetRelation('emailVerificationOtp');

        event(new Verified($user));

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $key = 'resend-otp:'.Str::lower($request->email);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return $this->rateLimited($key);
        }
        RateLimiter::hit($key, 600);

        $user = User::where('email', $request->email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // Generic response either way — no account enumeration.
        return response()->json(['message' => 'If an account exists, a new code has been sent.']);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->rateLimited($throttleKey, 'ACCOUNT_THROTTLED');
        }

        if (! Auth::attempt($credentials, true)) {
            RateLimiter::hit($throttleKey, 60);

            return $this->error('INVALID_CREDENTIALS', 'Email or password is incorrect.', 401);
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            Auth::logout();

            return $this->error('EMAIL_VERIFICATION_REQUIRED', 'Verify your email to continue.', 403);
        }

        $request->session()->regenerate();

        return response()->json(['user' => $user]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Identical response whether or not the account exists.
        return response()->json(['message' => 'If an account exists, a password reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                // Invalidate every existing session/token for this account.
                $user->tokens()->delete();
                \DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error('RESET_LINK_INVALID', 'This reset link is invalid or has expired.', 410);
        }

        return response()->json(['message' => 'Password reset. Sign in with your new password.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    private function rateLimited(string $key, string $code = 'RATE_LIMITED'): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => 'Too many attempts. Try again shortly.',
                'retry_after' => RateLimiter::availableIn($key),
            ],
        ], 429);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
