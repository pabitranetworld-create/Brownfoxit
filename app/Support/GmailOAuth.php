<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * "Connect with Google": the owner signs in once with their Gmail account and allows the site to *send*
 * email (scope gmail.send only — the site cannot read the mailbox). Google returns a refresh token, which
 * is stored encrypted. No Google password is ever seen or stored by the site.
 */
class GmailOAuth
{
    public const SCOPES = 'openid email https://www.googleapis.com/auth/gmail.send';

    public static function cfg(): array
    {
        return (array) setting('mail', []);
    }

    public static function secret(string $key): ?string
    {
        $v = self::cfg()[$key] ?? null;

        return $v ? rescue(fn () => Crypt::decryptString($v), null, false) : null;
    }

    public static function hasClient(): bool
    {
        return filled(self::cfg()['gmail_client_id'] ?? null) && filled(self::secret('gmail_client_secret'));
    }

    public static function connected(): bool
    {
        return filled(self::secret('gmail_refresh_token')) && filled(self::cfg()['gmail_email'] ?? null);
    }

    public static function redirectUri(): string
    {
        return route('admin.mail.google.callback');
    }

    public static function authUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => self::cfg()['gmail_client_id'], 'redirect_uri' => self::redirectUri(), 'response_type' => 'code',
            'scope' => self::SCOPES, 'access_type' => 'offline', 'prompt' => 'consent', 'include_granted_scopes' => 'true', 'state' => $state,
        ]);
    }

    /** Exchange the code from Google for tokens; returns the Gmail address. */
    public static function exchange(string $code): string
    {
        $res = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'code' => $code, 'client_id' => self::cfg()['gmail_client_id'], 'client_secret' => self::secret('gmail_client_secret'),
            'redirect_uri' => self::redirectUri(), 'grant_type' => 'authorization_code',
        ]);
        if (! $res->successful() || ! $res->json('refresh_token')) {
            throw new RuntimeException('Google did not return a token: '.($res->json('error_description') ?: $res->json('error') ?: 'HTTP '.$res->status()));
        }
        $email = self::emailFromIdToken((string) $res->json('id_token'));
        if (! $email) {
            $info = Http::withToken($res->json('access_token'))->timeout(15)->get('https://openidconnect.googleapis.com/v1/userinfo');
            $email = $info->json('email');
        }
        if (! $email) {
            throw new RuntimeException('Could not read the Gmail address.');
        }
        $m = self::cfg();
        $m['gmail_refresh_token'] = Crypt::encryptString($res->json('refresh_token'));
        $m['gmail_email'] = $email;
        $m['mailer'] = 'gmail_api';
        $m['from_address'] = $email;
        Setting::put('mail', $m);
        Cache::put('gmail_access_token', Crypt::encryptString($res->json('access_token')), now()->addSeconds(max(60, (int) $res->json('expires_in', 3600) - 120)));

        return $email;
    }

    protected static function emailFromIdToken(string $jwt): ?string
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return ($payload['email_verified'] ?? false) ? ($payload['email'] ?? null) : null;
    }

    public static function accessToken(): string
    {
        if ($cached = Cache::get('gmail_access_token')) {
            if ($t = rescue(fn () => Crypt::decryptString($cached), null, false)) {
                return $t;
            }
        }
        $res = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => self::cfg()['gmail_client_id'] ?? '', 'client_secret' => self::secret('gmail_client_secret'),
            'refresh_token' => self::secret('gmail_refresh_token'), 'grant_type' => 'refresh_token',
        ]);
        if (! $res->successful() || ! $res->json('access_token')) {
            throw new RuntimeException('Gmail connection expired or was removed ('.($res->json('error') ?: 'HTTP '.$res->status()).'). Connect again in Admin → ইমেইল / Gmail.');
        }
        Cache::put('gmail_access_token', Crypt::encryptString($res->json('access_token')), now()->addSeconds(max(60, (int) $res->json('expires_in', 3600) - 120)));

        return $res->json('access_token');
    }

    public static function disconnect(): void
    {
        $token = self::secret('gmail_refresh_token');
        if ($token) {
            rescue(fn () => Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', ['token' => $token]), null, false);
        }
        $m = self::cfg();
        unset($m['gmail_refresh_token'], $m['gmail_email']);
        if (($m['mailer'] ?? '') === 'gmail_api') {
            $m['mailer'] = 'off';
        }
        Setting::put('mail', $m);
        Cache::forget('gmail_access_token');
    }
}
