<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailOtpNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'timezone', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'erasure_completed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function careerProfile(): HasOne
    {
        return $this->hasOne(CareerProfile::class);
    }

    public function careerGoal(): HasOne
    {
        return $this->hasOne(CareerGoal::class);
    }

    public function emailVerificationOtp(): HasOne
    {
        return $this->hasOne(EmailVerificationOtp::class);
    }

    /**
     * Generates a fresh 6-digit code, replacing any existing one, and emails
     * it. Called on registration and on every resend request.
     *
     * The code is persisted before the send attempt, so a mail-transport
     * failure (bad SMTP credentials, provider outage) never breaks
     * registration or resend — it only means delivery didn't happen this
     * time. The failure is logged so it's visible in
     * 13-observability-and-runbooks.md's "email delivery failure" runbook,
     * not swallowed silently.
     */
    public function sendEmailVerificationNotification(): void
    {
        $code = (string) random_int(100000, 999999);

        $this->emailVerificationOtp()->updateOrCreate(
            ['user_id' => $this->id],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
            ]
        );

        try {
            $this->notify(new VerifyEmailOtpNotification($code));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('otp_email_delivery_failed', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Password reset link points at the frontend, which collects the new
     * password and calls POST /auth/password/reset.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = rtrim(config('app.frontend_url'), '/').'/reset-password?token='.$token.'&email='.urlencode($this->email);

        try {
            $this->notify(new ResetPasswordNotification($url));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('password_reset_email_delivery_failed', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
