<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Queued — "Email sending" is a background job class per docs/06 §7 /
 * ADR-002. Dispatch (this class going onto the `jobs` table) is a DB insert
 * and can't fail for mail-transport reasons; only the queue worker's actual
 * send attempt can. Bounded retries + failed() give that failure a defined,
 * observable outcome instead of a silent drop, per 13-observability-and-
 * runbooks.md's "email delivery failure" runbook.
 */
class VerifyEmailOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public string $code) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your verification code — JB AI Career Agent')
            ->line('Enter this code to verify your email address:')
            ->line(new \Illuminate\Support\HtmlString(
                '<div style="font-size:32px;font-weight:600;letter-spacing:8px;text-align:center;margin:24px 0;">'.$this->code.'</div>'
            ))
            ->line('This code expires in 10 minutes.')
            ->line("If you didn't request this, no action is required.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('otp_email_delivery_failed', [
            'notifiable_id' => $this->id ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
