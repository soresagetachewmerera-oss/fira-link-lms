<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\EventHighlight;
use App\Models\EventSpeaker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MasterclassEventSeeder extends Seeder
{
    public function run(): void
    {
        $title = 'በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class !” እና የ Fira Link A8,A9,A10 online Export Business class የ certificate ዝግጅት እና ማጠቃለያ ስልጠና !!"';
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';

        $summary = 'በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class እና የ Fira Link A8, A9, A10 ኦንላይን ኤክስፖርት ቢዝነስ ክፍል የሰርተፊኬት ዝግጅት እና ማጠቃለያ ስልጠና። ነጻ መግቢያ እና ምዝገባ።';

        $description = <<<TEXT
በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class !” እና የ Fira Link A8,A9,A10 online Export Business class የ certificate ዝግጅት እና ማጠቃለያ ስልጠና !!"

በዚህ ልዩ የተግባር ማስተር ክላስ ውስጥ የሚከተሉት ዋና ዋና ርዕሶች በዝርዝር ይዳሰሳሉ፡

1. የተግባር የቡና እና የጥራጥሬ ኤክስፖርት አሰራር (Practical Coffee & Pulses Export):
• የቡና እና የጥራጥሬ ሰብሎች የገበያ ሁኔታ፣ የዋጋ ስሌት (FOB, CIF) እና የአዋጭነት ትንተና።
• የኢትዮጵያ ቡና እና ሻይ ባለስልጣን (ECTA) እና የብሔራዊ ባንክ (NBE) የውጭ ምንዛሬ ህጎች እና መመሪያዎች።
• ዓለም አቀፍ የገዢዎች ፍላጎት፣ ኮንትራት አዘገጃጀት እና የጥራት ደረጃዎች (Quality & Grading Specifications)።

2. የ Fira Link A8, A9 እና A10 ኦንላይን ኤክስፖርት ቢዝነስ ክፍል ማጠቃለያ እና የሰርተፊኬት ዝግጅት፡
• በ A8, A9 እና A10 ዙር የተሳተፉ ሰልጣኞች ስልጠናቸውን በተሳካ ሁኔታ በማጠናቀቃቸው የሰርተፊኬት ዝግጅት፣ ማረጋገጫ እና አሰጣጥ ሂደት ገለፃ።
• የተግባር ፕሮጀክቶች ግምገማ እና የቀጣይ ቢዝነስ ማገናኘት (Business Linkage) ድጋፍ።
• የኤክስፖርት ንግድ ፍቃድ፣ የገበያ መረጃ እና የ Fira Link B2B ማርኬትፕሌስ ተጠቃሚነት መመሪያዎች።

3. የቀጥታ ጥያቄና መልስ እና የኔትወርኪንግ መድረክ (Live Q&A & Networking):
• ከተሞከሩ የዘርፉ ባለሙያዎች እና ከተሳካላቸው ላኪዎች ጋር የቀጥታ የውይይት መድረክ።
• ነጻ የመግቢያ ትኬት በማስያዝ በቀጥታ በኦንላይን ይሳተፉ!
TEXT;

        $locationDescription = 'ይህ መርሃግብር በቀጥታ በኦንላይን (Fira Link Virtual Hall & Zoom Live Session) የሚካሄድ ሲሆን የተመዘገቡ ተሳታፊዎች የመግቢያ ሊንክ እና የትኬት ማረጋገጫ በቀጥታ ይደርሳቸዋል።';

        $startDate = Carbon::now()->addDays(7)->format('Y-m-d');
        $endDate = $startDate;

        $event = Event::updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $title,
                'summary' => $summary,
                'category' => 'training',
                'description' => $description,
                'location_description' => $locationDescription,
                'location' => 'Online (Fira Link Virtual Hall & Zoom) / አዲስ አበባ፣ ኢትዮጵያ',
                'is_online' => true,
                'contact_phone' => '+251911000000',
                'contact_email' => 'academy@fira-link.com',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => '09:00:00',
                'end_time' => '13:00:00',
                'total_seats' => 500,
                'price' => 0.00,
                'member_price' => 0.00,
                'service_fee' => 0.00,
                'refund_policy' => 'ነጻ መግቢያ - ቅድመ ምዝገባ ብቻ ያስፈልጋል። (Free Admission - Pre-registration only)',
                'organized_by_fira_link' => true,
                'is_published' => true,
                'featured_image' => 'events/event-5-export-contracts-logistics.jpg',
                'agenda' => [
                    [
                        'time' => '09:00 - 10:15',
                        'title' => 'የቡና እና የጥራጥሬ ኤክስፖርት የተግባር አሰራር እና የገበያ ዋጋ ስሌት',
                        'speaker' => 'Fira Link Export Specialist',
                    ],
                    [
                        'time' => '10:15 - 11:30',
                        'title' => 'የህግ ማዕቀፎች፣ የ ECTA እና NBE ደንቦች፣ የጥራት ማረጋገጫ እና ውሎች',
                        'speaker' => 'Regulatory & Trade Advisor',
                    ],
                    [
                        'time' => '11:30 - 12:30',
                        'title' => 'የ A8, A9, A10 ተመራቂዎች የሰርተፊኬት ዝግጅት እና የማጠቃለያ ስልጠና ገለፃ',
                        'speaker' => 'Fira Link Academy Coordinator',
                    ],
                    [
                        'time' => '12:30 - 13:00',
                        'title' => 'የጥያቄና መልስ መድረክ፣ ኔትወርኪንግ እና የትብብር አቅጣጫዎች',
                        'speaker' => 'All Speakers & Participants',
                    ],
                ],
                'metadata' => [
                    'currency' => 'ETB',
                    'is_free' => true,
                    'ticket_type' => 'Free Admission',
                    'language' => 'Amharic',
                    'batch' => 'A8, A9, A10',
                ],
            ]
        );

        // Highlights
        $event->highlights()->delete();
        $highlights = [
            [
                'title' => 'የተግባር የቡና እና የጥራጥሬ ኤክስፖርት ስሌት',
                'description' => 'የቡና እና የጥራጥሬ ሰብሎች የገበያ ሁኔታ፣ የዋጋ ስሌት (FOB, CIF) እና የአዋጭነት ትንተና',
                'position' => 1,
            ],
            [
                'title' => 'የ ECTA እና NBE የውጭ ንግድ ህጎች',
                'description' => 'የኢትዮጵያ ቡና እና ሻይ ባለስልጣን እና የብሔራዊ ባንክ የውጭ ምንዛሬ ህጎች እና መመሪያዎች',
                'position' => 2,
            ],
            [
                'title' => 'A8, A9, A10 የሰርተፊኬት ዝግጅት እና ማረጋገጫ',
                'description' => 'የ Fira Link A8, A9, A10 ኦንላይን ኤክስፖርት ቢዝነስ ክፍል ተመራቂዎች የሰርተፊኬት ዝግጅት፣ ማረጋገጫ እና አሰጣጥ',
                'position' => 3,
            ],
            [
                'title' => 'የቀጥታ የጥያቄና መልስ እና የኔትወርኪንግ መድረክ',
                'description' => 'ከተሞከሩ የዘርፉ ባለሙያዎች ጋር የቀጥታ ውይይት እና የነጻ ትኬት ምዝገባ',
                'position' => 4,
            ],
        ];

        foreach ($highlights as $h) {
            $event->highlights()->create($h);
        }

        // Speakers
        $event->speakers()->delete();
        $speakers = [
            [
                'name' => 'የ Fira Link ኤክስፖርት አሰልጣኞች ቡድን',
                'title' => 'Senior Coffee & Pulses Export Specialists',
                'bio' => 'በኢትዮጵያ የቡና እና የጥራጥሬ ምርቶች የውጭ ንግድ፣ የ ECTA እና NBE ህጎች፣ የውል አሰራር እና አለምአቀፍ ገበያ ትስስር ላይ ሰፊ ልምድ ያለው የአሰልጣኞች ቡድን።',
                'position' => 1,
            ],
            [
                'name' => 'የ Fira Link አካዳሚ ማስተባበሪያ',
                'title' => 'Fira Link Export Academy Coordinator',
                'bio' => 'የ Fira Link A8, A9, A10 ተከታታይ ባቾች ስልጠና አስተባባሪ እና የሰርተፊኬት ዝግጅት ኃላፊ።',
                'position' => 2,
            ],
        ];

        foreach ($speakers as $spk) {
            $event->speakers()->create($spk);
        }

        // Add confirmed free booked tickets
        $user = User::first();
        if ($user) {
            EventBooking::updateOrCreate(
                ['reference' => 'EVT-FREE-MASTERCLASS-01'],
                [
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'status' => EventBooking::STATUS_CONFIRMED,
                    'payment_status' => EventBooking::PAYMENT_PAID,
                    'first_name' => 'Abebe',
                    'last_name' => 'Bekele',
                    'email' => $user->email ?? 'attendee@fira-link.com',
                    'phone' => '+251911223344',
                    'company' => 'Coffee & Pulses Exporters Cohort A8',
                    'ticket_type' => 'general',
                    'seats' => 1,
                    'unit_price' => 0.00,
                    'service_fee' => 0.00,
                    'total_amount' => 0.00,
                    'currency' => 'ETB',
                    'payment_method' => 'free',
                    'contact_method' => 'email',
                    'notes' => 'A8 Batch Graduate - Free Masterclass & Certificate Session Attendance',
                    'booked_at' => Carbon::now(),
                ]
            );

            EventBooking::updateOrCreate(
                ['reference' => 'EVT-FREE-MASTERCLASS-02'],
                [
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'status' => EventBooking::STATUS_CONFIRMED,
                    'payment_status' => EventBooking::PAYMENT_PAID,
                    'first_name' => 'Selam',
                    'last_name' => 'Haile',
                    'email' => 'selam@fira-link.com',
                    'phone' => '+251922334455',
                    'company' => 'Export Business Class A9',
                    'ticket_type' => 'general',
                    'seats' => 1,
                    'unit_price' => 0.00,
                    'service_fee' => 0.00,
                    'total_amount' => 0.00,
                    'currency' => 'ETB',
                    'payment_method' => 'free',
                    'contact_method' => 'phone',
                    'notes' => 'A9 Batch Graduate - Free Masterclass Seat Confirmation',
                    'booked_at' => Carbon::now(),
                ]
            );
        }
    }
}
