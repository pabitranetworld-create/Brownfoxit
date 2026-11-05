<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'ইমেইল দিন।',
            'email.email' => 'সঠিক ইমেইল দিন।',
            'password.required' => 'পাসওয়ার্ড দিন।',
        ]);

        // 5 wrong tries per minute per email + IP
        $key = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->onlyInput('email')->withErrors(['email' => "অনেকবার ভুল হয়েছে। {$seconds} সেকেন্ড পরে আবার চেষ্টা করুন।"]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return back()->onlyInput('email')->withErrors(['email' => 'ইমেইল বা পাসওয়ার্ড ভুল।']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        if (! Auth::user()->isAdmin()) {
            Auth::logout();

            return back()->onlyInput('email')->withErrors(['email' => 'এই অ্যাকাউন্টের Admin অনুমতি নেই।']);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'আপনি লগআউট করেছেন।');
    }
}
