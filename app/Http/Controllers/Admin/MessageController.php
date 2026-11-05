<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Support\SiteMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Inbox for the contact and quote forms. */
class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));

        $items = FormSubmission::query()
            ->when($status !== '', fn ($w) => $w->where('status', $status), fn ($w) => $w->whereNotIn('status', ['archived', 'spam']))
            ->when(isset(FormSubmission::TYPES[$type]), fn ($w) => $w->where('type', $type))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('message', 'like', "%{$q}%")->orWhere('company', 'like', "%{$q}%")))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.messages.index', [
            'items' => $items, 'status' => $status, 'type' => $type, 'q' => $q,
            'counts' => FormSubmission::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'mailReady' => SiteMail::configured(),
        ]);
    }

    public function show(FormSubmission $message): View
    {
        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return view('admin.messages.show', ['m' => $message]);
    }

    public function update(Request $request, FormSubmission $message): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(FormSubmission::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);
        $message->update($data);

        return back()->with('status', 'সেভ হয়েছে।');
    }

    public function destroy(FormSubmission $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'বার্তাটা মুছে ফেলা হয়েছে।');
    }
}
