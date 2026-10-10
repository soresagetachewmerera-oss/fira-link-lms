<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Newsletter Controller
 *
 * This controller handles the newsletter subscription functionality.
 *
 * @package App\Http\Controllers\Front
 */
class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:190', 'unique:newsletter_subscribers,email'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        NewsletterSubscriber::create([
            'email' => $data['email'],
            'name' => $data['name'] ?? null,
            'source' => $request->input('source', 'homepage'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thanks for subscribing! Please check your inbox for future updates.',
        ]);
    }
}

