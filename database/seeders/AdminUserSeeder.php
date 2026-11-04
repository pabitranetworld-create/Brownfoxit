<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('site.admin');

        $user = User::firstOrNew(['email' => $admin['email']]);

        $user->fill([
            'name' => $user->name ?: $admin['name'],
            'role' => User::ROLE_ADMIN,
            'avatar' => $user->avatar ?: 'uploads/avatars/admin1.jpg',
        ]);

        // Only set the password the first time, so re-seeding never resets it.
        if (! $user->exists) {
            $user->password = $admin['password'];
            $user->email_verified_at = now();
        }

        $user->save();
    }
}
