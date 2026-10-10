<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\FrontendSetting;
use Illuminate\Contracts\View\View;

/**
 * FAQ Controller
 *
 * This controller handles the FAQ functionality.
 *
 * @package App\Http\Controllers\Front
 */
class FaqController extends Controller
{
    public function __invoke(): View
    {
        $faqItems = collect(FrontendSetting::getValue('pages.faq.items', []))
            ->filter(function ($item) {
                return filled($item['question'] ?? null) || filled($item['answer'] ?? null);
            })
            ->map(function ($item) {
                return [
                    'question' => $item['question'] ?? '',
                    'answer' => $item['answer'] ?? '',
                ];
            })
            ->values();

        $meta = frontend_meta('faq', null, []);

        return view('faq', [
            'pageTitle' => $meta['title'] ?? 'FAQS',
            'metaDescription' => $meta['description'] ?? null,
            'faqItems' => $faqItems,
        ]);
    }
}

