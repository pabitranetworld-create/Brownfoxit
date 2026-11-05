<?php

namespace App\Http\Controllers\Admin;

use App\Admin\FormData;
use App\Admin\Schema;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Edits the home-page blocks stored in the `settings` table.
 * Sections with a "root" repeater (menu, counters) store a plain list.
 */
class SettingsController extends Controller
{
    public function edit(string $section): View
    {
        $def = Schema::section($section);
        $stored = Setting::get($section, []);

        return view('admin.settings.edit', [
            'key' => $section,
            'section' => $def,
            'fields' => $this->fields($def),
            'values' => old('data', isset($def['root']) ? ['_root' => $stored] : (array) $stored),
            'sections' => Schema::sections(),
        ]);
    }

    public function update(Request $request, string $section, FormData $form): RedirectResponse
    {
        $def = Schema::section($section);
        $fields = $this->fields($def);
        $input = (array) $request->input('data', []);
        $files = (array) $request->file('data_files', []);

        if (! $form->validate($fields, $input, $files)) {
            return back()->withInput()->withErrors($form->errors);
        }

        $values = $form->values($fields, $input, $files);

        // Passwords: keep the stored one when left empty, encrypt new ones.
        foreach ($fields as $f) {
            if ($f['type'] === 'password') {
                if (blank($values[$f['key']] ?? null)) {
                    unset($values[$f['key']]);
                } else {
                    $values[$f['key']] = \Illuminate\Support\Facades\Crypt::encryptString((string) $values[$f['key']]);
                }
            }
        }

        if (isset($def['root'])) {
            $value = $values['_root'];
        } else {
            // Keep keys the form does not know about (so nothing is lost).
            $value = array_merge((array) Setting::get($section, []), $values);
        }

        Setting::put($section, $value);

        return redirect()->route('admin.settings.edit', $section)->with('status', "\"{$def['title']}\" সেভ হয়েছে।");
    }

    /** Admin → Settings → Email: send a test message with the saved settings. */
    public function testMail(): RedirectResponse
    {
        $to = \App\Support\SiteMail::adminAddress();
        if (! \App\Support\SiteMail::configured()) {
            return back()->withErrors(['mail' => 'আগে "ইমেইল পাঠানো" SMTP করে সেভ করুন।']);
        }
        if (! $to) {
            return back()->withErrors(['mail' => '"নতুন বার্তা কোন ইমেইলে আসবে" ঘরে একটা ইমেইল দিন।']);
        }
        $ok = \App\Support\SiteMail::send($to, 'Test email from '.setting('site.name', config('app.name')), "This is a test message from your website.\nIf you can read it, email sending works.");

        return $ok
            ? back()->with('status', "টেস্ট ইমেইল পাঠানো হয়েছে: {$to} — ইনবক্স (আর Spam ফোল্ডার) দেখুন।")
            : back()->withErrors(['mail' => 'ইমেইল পাঠানো যায়নি। Host, Port, Encryption, Username ও Password আবার দেখুন। (বিস্তারিত: storage/logs/laravel.log)']);
    }

    protected function fields(array $def): array
    {
        if (isset($def['root'])) {
            return [array_merge($def['root'], ['key' => '_root'])];
        }

        return $def['fields'];
    }
}
