<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\GmailOAuth;
use App\Support\SiteMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/** Email setup: Gmail (App Password or "Connect with Google"), or any other SMTP server. */
class MailController extends Controller
{
    public function index(): View
    {
        $m = (array) setting('mail', []);

        return view('admin.mail.index', [
            'm' => $m, 'mailer' => $m['mailer'] ?? 'off', 'ready' => SiteMail::configured(),
            'isGmailSmtp' => ($m['mailer'] ?? '') === 'smtp' && str_contains((string) ($m['host'] ?? ''), 'gmail.com'),
            'hasClient' => GmailOAuth::hasClient(), 'connected' => GmailOAuth::connected(),
            'redirectUri' => GmailOAuth::redirectUri(), 'notify' => SiteMail::adminAddress(),
        ]);
    }

    /** Gmail with an App Password: smtp.gmail.com, port 465, SSL — filled in automatically. */
    public function appPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gmail' => ['required', 'email', 'max:190'],
            'app_password' => ['nullable', 'string', 'max:40'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'notify_email' => ['nullable', 'email', 'max:190'],
        ]);
        $m = (array) setting('mail', []);
        $pass = preg_replace('/\s+/', '', (string) ($data['app_password'] ?? ''));
        if ($pass === '' && ! (($m['host'] ?? '') === 'smtp.gmail.com' && filled($m['password'] ?? null))) {
            return back()->withErrors(['app_password' => 'Enter the 16-letter App Password from your Google account.']);
        }
        if ($pass !== '' && ! preg_match('/^[a-z]{16}$/i', $pass)) {
            return back()->withErrors(['app_password' => 'An App Password has 16 letters (spaces do not matter). Your normal Gmail password will not work.']);
        }
        $m = array_merge($m, [
            'mailer' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 465, 'encryption' => 'ssl',
            'username' => $data['gmail'], 'from_address' => $data['gmail'],
            'from_name' => $data['from_name'] ?? null ?: ($m['from_name'] ?? setting('site.name')),
            'notify_email' => $data['notify_email'] ?? null ?: ($m['notify_email'] ?? $data['gmail']),
        ]);
        if ($pass !== '') {
            $m['password'] = Crypt::encryptString($pass);
        }
        Setting::put('mail', $m);

        return $this->testAndReport('Gmail (App Password) সেট হয়েছে।');
    }

    public function saveClient(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'string', 'max:255', 'regex:/\.apps\.googleusercontent\.com$/'],
            'client_secret' => ['nullable', 'string', 'max:255'],
        ], ['client_id.regex' => 'The Client ID ends with .apps.googleusercontent.com']);
        $m = (array) setting('mail', []);
        $m['gmail_client_id'] = trim($data['client_id']);
        if (filled($data['client_secret'] ?? null)) {
            $m['gmail_client_secret'] = Crypt::encryptString(trim($data['client_secret']));
        }
        Setting::put('mail', $m);
        if (! GmailOAuth::hasClient()) {
            return back()->withErrors(['client_secret' => 'Enter the Client secret too.']);
        }

        return back()->with('status', 'Google Client সেভ হয়েছে। এখন "Connect with Google" চাপুন।');
    }

    public function connect(Request $request): RedirectResponse
    {
        if (! GmailOAuth::hasClient()) {
            return back()->withErrors(['client_id' => 'First save the Google Client ID and secret.']);
        }
        $state = Str::random(40);
        $request->session()->put('gmail_oauth_state', $state);

        return redirect()->away(GmailOAuth::authUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expected = $request->session()->pull('gmail_oauth_state');
        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('admin.mail.index')->withErrors(['client_id' => 'The Google sign-in could not be verified (state mismatch). Please try again.']);
        }
        if ($request->query('error')) {
            return redirect()->route('admin.mail.index')->withErrors(['client_id' => 'Google: '.$request->query('error')]);
        }
        try {
            $email = GmailOAuth::exchange((string) $request->query('code'));
        } catch (Throwable $e) {
            return redirect()->route('admin.mail.index')->withErrors(['client_id' => $e->getMessage()]);
        }

        return $this->testAndReport('Gmail যুক্ত হয়েছে: '.$email.'.');
    }

    public function disconnect(): RedirectResponse
    {
        GmailOAuth::disconnect();

        return back()->with('status', 'Gmail-এর সংযোগ সরানো হয়েছে।');
    }

    public function test(): RedirectResponse
    {
        return $this->testAndReport('');
    }

    public function off(): RedirectResponse
    {
        $m = (array) setting('mail', []);
        $m['mailer'] = 'off';
        Setting::put('mail', $m);

        return back()->with('status', 'ইমেইল পাঠানো বন্ধ করা হয়েছে (বার্তা শুধু Admin-এ জমা থাকবে)।');
    }

    protected function testAndReport(string $prefix): RedirectResponse
    {
        $to = SiteMail::adminAddress();
        if (! SiteMail::configured() || ! $to) {
            return redirect()->route('admin.mail.index')->with('status', trim($prefix.' টেস্ট পাঠানো যায়নি — সেটিং অসম্পূর্ণ।'));
        }
        $ok = SiteMail::send($to, 'Test email from '.setting('site.name', config('app.name')), "This is a test email from your website.\n\nIf you can read this, email sending works.\n\n".url('/'));

        return $ok
            ? redirect()->route('admin.mail.index')->with('status', trim($prefix.' টেস্ট ইমেইল পাঠানো হয়েছে '.$to.' ঠিকানায় — Inbox (আর Spam) দেখুন।'))
            : redirect()->route('admin.mail.index')->withErrors(['app_password' => trim($prefix.' কিন্তু টেস্ট ইমেইল যায়নি। বিস্তারিত storage/logs-এ আছে; সাধারণত App Password ভুল হলে বা Google সংযোগ বাতিল হলে এমন হয়।')]);
    }
}
