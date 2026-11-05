<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Email through the SMTP account set in Admin → Settings → Email (SMTP).
 * Without SMTP nothing is sent; messages still land in the admin inbox.
 */
class SiteMail
{
    public static function configured(): bool
    {
        $m = (array) setting('mail', []);

        return ($m['mailer'] ?? 'off') === 'smtp' && filled($m['host'] ?? null) || ($m['mailer'] ?? 'off') === 'sendmail';
    }

    /** Apply the stored settings to Laravel's mail config. */
    public static function configure(): bool
    {
        if (! self::configured()) {
            return false;
        }
        $m = (array) setting('mail', []);
        $password = '';
        if (filled($m['password'] ?? null)) {
            $password = rescue(fn () => Crypt::decryptString($m['password']), '', false);
        }
        $from = $m['from_address'] ?? null ?: setting('site.email') ?: config('mail.from.address');
        config([
            'mail.default' => $m['mailer'],
            'mail.mailers.smtp.host' => $m['host'] ?? null,
            'mail.mailers.smtp.port' => (int) ($m['port'] ?? 587),
            'mail.mailers.smtp.scheme' => ($m['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.username' => $m['username'] ?? null,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.timeout' => 20,
            'mail.from.address' => $from,
            'mail.from.name' => $m['from_name'] ?? null ?: setting('site.name', config('app.name')),
        ]);
        app()->forgetInstance('mail.manager');
        Mail::clearResolvedInstances();

        return true;
    }

    /** Where admin notifications go. */
    public static function adminAddress(): ?string
    {
        $m = (array) setting('mail', []);

        return ($m['notify_email'] ?? null) ?: setting('site.email') ?: config('site.admin.email');
    }

    /** Send a plain-text email. Returns true when it was handed to the mail server. */
    public static function send(string $to, string $subject, string $text, ?string $replyTo = null): bool
    {
        if (! self::configure()) {
            return false;
        }
        try {
            Mail::raw($text, function ($msg) use ($to, $subject, $replyTo) {
                $msg->to($to)->subject($subject);
                if ($replyTo) {
                    $msg->replyTo($replyTo);
                }
            });

            return true;
        } catch (Throwable $e) {
            Log::warning('Email could not be sent: '.$e->getMessage());

            return false;
        }
    }
}
