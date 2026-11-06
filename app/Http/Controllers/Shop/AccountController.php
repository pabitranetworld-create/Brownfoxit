<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\DownloadPermission;
use App\Models\Order;
use App\Models\User;
use App\Support\SiteMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Customer accounts: login, registration, password reset, dashboard, orders, downloads, profile. */
class AccountController extends Controller
{
    public function loginForm(): View
    {
        return view('account.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'The email or password is not correct.']);
        }
        $request->session()->regenerate();
        $this->claimGuestOrders($request->user());

        return redirect()->intended(route('account.dashboard'));
    }

    public function registerForm(): View
    {
        abort_unless(store('registration', true), 404);

        return view('account.register');
    }

    public function register(Request $request): RedirectResponse
    {
        abort_unless(store('registration', true), 404);
        if (filled($request->input('website'))) {
            return redirect()->route('account.login'); // honeypot
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ], ['email.unique' => 'An account with this email already exists. Try logging in or resetting your password.']);
        $user = User::create($data + ['role' => User::ROLE_CUSTOMER]);
        Auth::login($user);
        $request->session()->regenerate();
        $this->claimGuestOrders($user);

        return redirect()->intended(route('account.dashboard'))->with('status', 'Welcome! Your account is ready.');
    }

    /** Orders placed as a guest with the same email appear in the new account. */
    protected function claimGuestOrders(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }
        Order::whereNull('user_id')->where('billing->email', $user->email)->update(['user_id' => $user->id]);
        DownloadPermission::whereNull('user_id')->whereIn('order_id', Order::where('user_id', $user->id)->pluck('id'))->update(['user_id' => $user->id]);
    }

    public function forgotForm(): View
    {
        return view('account.forgot');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $request->input('email'))->first();
        // Same answer whether or not the account exists (no account fishing).
        $msg = 'If an account exists for that email, a reset link is on its way.';
        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(['email' => $user->email], ['token' => Hash::make($token), 'created_at' => now()]);
            $link = route('account.reset', ['token' => $token, 'email' => $user->email]);
            if (! SiteMail::send($user->email, 'Reset your password — '.setting('site.name'), "Hi {$user->name},\n\nUse this link to choose a new password (valid for 60 minutes):\n{$link}\n\nIf you did not ask for this, you can ignore this email.")) {
                $msg = 'We could not send the email right now. Please contact us and we will help you.';
            }
        }

        return back()->with('status', $msg);
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('account.reset', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed']]);
        $row = DB::table('password_reset_tokens')->where('email', $data['email'])->first();
        if (! $row || ! Hash::check($data['token'], $row->token) || now()->subMinutes(60)->gt($row->created_at)) {
            return back()->withErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.']);
        }
        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update(['password' => $data['password'], 'remember_token' => Str::random(60)]);
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')->with('status', 'Your password has been changed.');
    }

    public function dashboard(Request $request): View
    {
        $u = $request->user();

        return view('account.dashboard', [
            'orders' => $u->orders()->with('items')->take(5)->get(),
            'downloadCount' => DownloadPermission::where('user_id', $u->id)->whereNull('revoked_at')->count(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('account.orders', ['orders' => $request->user()->orders()->with('items')->paginate(15)]);
    }

    public function downloads(Request $request): View
    {
        return view('account.downloads', ['downloads' => DownloadPermission::where('user_id', $request->user()->id)->with(['order', 'product'])->latest('id')->get()]);
    }

    public function profile(): View
    {
        return view('account.profile');
    }

    public function saveProfile(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:190', Rule::unique('users', 'email')->ignore($u->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:80'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'max:100', 'confirmed'],
        ], ['current_password.current_password' => 'Your current password is not correct.']);
        $u->fill(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'address' => array_intersect_key($data, array_flip(['address', 'city', 'postcode', 'country']))]);
        if (filled($data['password'] ?? null)) {
            $u->password = $data['password'];
        }
        $u->save();

        return back()->with('status', 'Your details have been saved.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
