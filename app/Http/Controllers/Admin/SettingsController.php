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

        if (isset($def['root'])) {
            $value = $values['_root'];
        } else {
            // Keep keys the form does not know about (so nothing is lost).
            $value = array_merge((array) Setting::get($section, []), $values);
        }

        Setting::put($section, $value);

        return redirect()->route('admin.settings.edit', $section)->with('status', "\"{$def['title']}\" সেভ হয়েছে।");
    }

    protected function fields(array $def): array
    {
        if (isset($def['root'])) {
            return [array_merge($def['root'], ['key' => '_root'])];
        }

        return $def['fields'];
    }
}
