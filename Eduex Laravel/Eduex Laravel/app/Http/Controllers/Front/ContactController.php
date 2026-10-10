<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactFormSubmitted;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Contact Controller
 *
 * This controller handles the contact functionality.
 *
 * @package App\Http\Controllers\Front
 */
class ContactController extends Controller
{
    public function index(): View
    {
        $meta = frontend_meta('contact', null, []);

        $heroSettings = frontend_setting('pages.contact.hero', []);
        $contactInfo = frontend_setting('footer.contact', []);
        $methodSettings = frontend_setting('pages.contact.methods', []);
        $formSettings = frontend_setting('pages.contact.form', []);
        $mapSettings = frontend_setting('pages.contact.map', []);

        $contactMethods = collect($methodSettings)
            ->map(function (array $method) use ($contactInfo) {
                $type = $method['type'] ?? null;
                $rawValue = $method['value'] ?? ($type ? Arr::get($contactInfo, $type) : null);
                $value = $rawValue ?: null;

                $link = $method['link'] ?? null;

                if (!$link && $value) {
                    if ($type === 'phone') {
                        $link = 'tel:' . preg_replace('/[^\d\+]/', '', $value);
                    } elseif ($type === 'email') {
                        $link = 'mailto:' . $value;
                    } elseif ($type === 'address' && isset($contactInfo['address_url'])) {
                        $link = $contactInfo['address_url'];
                    }
                }

                return [
                    'icon' => $method['icon'] ?? 'fa-light fa-circle-info',
                    'label' => $method['label'] ?? Str::title((string) $type),
                    'value' => $value,
                    'link' => $link,
                    'type' => $type,
                ];
            })
            ->filter(function (array $method) {
                return filled($method['value']);
            })
            ->values();

        return view('contact-us', [
            'pageTitle' => $meta['title'] ?? 'Contact Us',
            'metaDescription' => $meta['description'] ?? null,
            'heroSettings' => $heroSettings,
            'contactMethods' => $contactMethods,
            'formSettings' => $formSettings,
            'mapSettings' => $mapSettings,
        ]);
    }

    public function submit(ContactRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $payload = array_merge($data, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user' => $request->user() ? $request->user()->only(['id', 'name', 'email']) : null,
        ]);

        $recipient = frontend_setting('footer.contact.email', config('mail.from.address'));
        $formSettings = frontend_setting('pages.contact.form', []);
        $subjectPrefix = $formSettings['subject_prefix'] ?? '[Contact]';
        $successMessage = $formSettings['success_message'] ?? 'Thank you for contacting us. We will respond shortly!';

        try {
            if ($recipient) {
                Mail::to($recipient)->send(new ContactFormSubmitted($payload, $subjectPrefix));
            } else {
                Log::warning('Contact form submission received but no recipient email is configured.', [
                    'payload' => Arr::except($payload, ['message']),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Failed to send contact message.', [
                'exception' => $exception,
            ]);

            $errorMessage = 'We could not send your message right now. Please try again later.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => $errorMessage], 500);
            }

            return back()->withInput()->with('error', $errorMessage);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $successMessage]);
        }

        return back()->with('success', $successMessage);
    }
}
