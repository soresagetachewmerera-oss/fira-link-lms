<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminNewsletterBlast;
use App\Models\NewsletterSubscriber;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Newsletter Controller
 *
 * This controller handles the newsletter management functionality.
 *
 * @package App\Http\Controllers\Admin
 */
class NewsletterController extends Controller
{
    public function index(): View
    {
        $subscribers = NewsletterSubscriber::latest()->paginate(20);

        return view('admin.newsletter.index', compact('subscribers'));
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string'],
            'recipients' => ['nullable', 'string'],
        ]);

        $emails = collect(explode(',', $data['recipients'] ?? ''))
            ->filter()
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values();

        if ($emails->isEmpty()) {
            $emails = NewsletterSubscriber::pluck('email');
        }

        if ($emails->isEmpty()) {
            ToastMagic::warning('No subscribers found to send the newsletter.');
            return back();
        }

        $primaryRecipient = config('mail.from.address') ?? $emails->first();
        $bcc = $emails->reject(fn ($email) => $email === $primaryRecipient)->values()->all();

        $mailable = new AdminNewsletterBlast($data['subject'], $data['message']);

        if (!empty($bcc)) {
            $mailable->bcc($bcc);
        }

        Mail::to($primaryRecipient)->send($mailable);

        ToastMagic::success('Newsletter sent to ' . $emails->count() . ' subscriber(s).');

        return back();
    }
}

