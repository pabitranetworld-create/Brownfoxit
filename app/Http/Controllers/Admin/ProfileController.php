<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required_with:password', 'nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'নাম দিন।',
            'email.required' => 'ইমেইল দিন।',
            'email.email' => 'সঠিক ইমেইল দিন।',
            'email.unique' => 'এই ইমেইল অন্য অ্যাকাউন্টে আছে।',
            'current_password.required_with' => 'নতুন পাসওয়ার্ড দিতে হলে বর্তমান পাসওয়ার্ড দিন।',
            'password.min' => 'নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।',
            'password.confirmed' => 'নতুন পাসওয়ার্ড দুটো মেলেনি।',
        ]);

        if (filled($data['password'] ?? null)) {
            if (! Hash::check((string) $data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'বর্তমান পাসওয়ার্ড ভুল।']);
            }
            $user->password = $data['password'];
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return back()->with('status', 'প্রোফাইল সেভ হয়েছে।');
    }
}
