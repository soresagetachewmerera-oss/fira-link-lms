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

        if ($faqItems->isEmpty()) {
            $faqItems = collect([
                [
                    'question' => 'What is Fira Link Platform?',
                    'answer' => 'Fira Link Platform is the digital learning and trade development division of Fira Link Business Solution, based in Addis Ababa, Ethiopia. We provide practical, operational training in import-export business management, customs clearance, commodity trade, and logistics corridor operations.',
                ],
                [
                    'question' => 'Who are the courses designed for?',
                    'answer' => 'Our programs cater to aspiring entrepreneurs, active commercial importers, coffee and agricultural commodity exporters, customs clearing agents, freight forwarders, and logistics professionals operating across Ethiopia and East Africa.',
                ],
                [
                    'question' => 'How are the training programs delivered?',
                    'answer' => 'We offer flexible online courses with lifetime access, downloadable trade templates, and live weekly Q&A sessions. Additionally, we conduct immersive physical trade expeditions, including coffee origin field tours to Sidama/Yirgacheffe and logistics dry port inspections.',
                ],
                [
                    'question' => 'Which payment methods are accepted for tuition?',
                    'answer' => 'We accept seamless domestic and regional payments via Chapa, Telebirr, CBE (Commercial Bank of Ethiopia) direct transfer, and major international payment options.',
                ],
                [
                    'question' => 'Do students receive a verifiable certificate?',
                    'answer' => 'Yes. Upon successfully completing all eligible paid course modules, assignments, and practical compliance assessments, graduates receive an official certificate from Fira Link Platform featuring a unique verification QR code verifiable on our portal.',
                ],
                [
                    'question' => 'Are the tools in the Free Trade Suite really free?',
                    'answer' => 'Yes. Our Ethiopian Coffee Export Suite, live benchmark market pricing terminal, corridor costing calculator, and AI trade advisor are freely available to all visitors without requiring paid subscriptions.',
                ],
                [
                    'question' => 'How can I get one-on-one trade advisory or corporate training?',
                    'answer' => 'You can reach our corporate consulting team via our Contact page or email info@firalink.com to schedule tailored enterprise training or bespoke cross-border trade advisory.',
                ],
            ]);
        }

        $brandName = config('seo.brand_name', 'Fira Link Platform');
        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'FAQ', 'url' => url('/faq')],
        ];

        return view('faq', [
            'pageTitle' => 'Frequently Asked Questions | ' . $brandName,
            'metaDescription' => 'Find clear answers to common questions about Fira Link Platform courses, enrollment, tuition, certificates, trade expeditions, and free business tools in Ethiopia.',
            'breadcrumbs' => $breadcrumbs,
            'faqItems' => $faqItems,
            'schemaFaqs' => $faqItems->all(),
        ]);
    }
}

