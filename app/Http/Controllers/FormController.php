<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Support\SiteMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Contact and quote forms from the page builder ("Form" widget).
 * Messages are stored (Admin → Messages) and emailed when SMTP is set up.
 * Spam protection: hidden honeypot field, minimum fill time, rate limit.
 */
class FormController extends Controller
{
    public function submit(Request $request, string $type): JsonResponse|RedirectResponse
    {
        abort_unless(isset(FormSubmission::TYPES[$type]), 404);

        $key = 'form:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->fail($request, 'অনেকগুলো বার্তা খুব দ্রুত পাঠানো হয়েছে। এক মিনিট পরে আবার চেষ্টা করুন।|Too many messages in a short time. Please try again in a minute.', 429);
        }
        RateLimiter::hit($key, 60);

        // Bots fill every field and submit instantly: pretend success, store nothing.
        $started = rescue(fn () => (int) Crypt::decryptString((string) $request->input('_t')), 0, false);
        if (filled($request->input('website')) || ($started && time() - $started < 3)) {
            return $this->done($request);
        }

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:190'],
            'phone' => ['nullable', 'string', 'max:60'],
            'message' => ['required', 'string', 'min:10', 'max:8000'],
            'consent' => ['accepted'],
        ];
        if ($type === 'contact') {
            $rules['subject'] = ['nullable', 'string', 'max:190'];
        } else {
            $rules += [
                'company' => ['nullable', 'string', 'max:190'],
                'service' => ['required', 'string', 'max:190'],
                'budget' => ['nullable', 'string', 'max:100'],
                'timeline' => ['nullable', 'string', 'max:100'],
                'reference' => ['nullable', 'url:http,https', 'max:500'],
            ];
        }
        $messages = [
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'message.required' => 'Please write a short message.',
            'message.min' => 'Please write at least a sentence (10 characters or more).',
            'service.required' => 'Please choose a service.',
            'consent.accepted' => 'Please confirm that we may store your details to answer you.',
            'reference.url' => 'Please enter a full link starting with https://',
        ];

        try {
            $data = $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Please check the highlighted fields.', 'errors' => $e->errors()], 422);
            }
            throw $e;
        }

        $sub = FormSubmission::create([
            'type' => $type,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'subject' => $data['subject'] ?? null,
            'service' => $data['service'] ?? null,
            'budget' => $data['budget'] ?? null,
            'timeline' => $data['timeline'] ?? null,
            'message' => $data['message'],
            'data' => array_filter(['reference' => $data['reference'] ?? null]),
            'page_url' => Str::limit((string) $request->headers->get('referer'), 490, ''),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 290, ''),
        ]);

        $to = SiteMail::adminAddress();
        if ($to) {
            $lines = ['New '.strtolower(FormSubmission::TYPES[$type]).' from '.setting('site.name', config('app.name')), ''];
            foreach (['name', 'email', 'phone', 'company', 'subject', 'service', 'budget', 'timeline'] as $f) {
                if (filled($sub->{$f})) {
                    $lines[] = ucfirst($f).': '.$sub->{$f};
                }
            }
            if (! empty($sub->data['reference'])) {
                $lines[] = 'Reference: '.$sub->data['reference'];
            }
            $lines = array_merge($lines, ['', $sub->message, '', 'Sent from: '.$sub->page_url, 'Admin: '.route('admin.messages.show', $sub)]);
            $subject = ($type === 'quote' ? 'Quote request' : 'Message').' — '.$sub->name.($sub->service ? ' ('.$sub->service.')' : '');
            if (SiteMail::send($to, $subject, implode("\n", $lines), $sub->email)) {
                $sub->update(['emailed' => true]);
            }
        }

        return $this->done($request);
    }

    protected function done(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('form_sent', true);
    }

    protected function fail(Request $request, string $message, int $code): JsonResponse|RedirectResponse
    {
        $en = str_contains($message, '|') ? explode('|', $message)[1] : $message;
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $en], $code);
        }

        return back()->withInput()->withErrors(['form' => $en]);
    }
}
