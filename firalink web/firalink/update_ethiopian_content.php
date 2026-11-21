<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tour;
use App\Models\Event;

echo "=== UPDATING TOURS WITH AUTHENTIC ETHIOPIAN ASSETS ===" . PHP_EOL;

$tourUpdates = [
    1 => [
        'featured_image' => 'tours/tour-1-yirgacheffe-origin-expedition.jpg',
        'gallery' => [
            'tours/tour-1-yirgacheffe-origin-expedition.jpg',
            'tours/tour-1-gallery-1.jpg',
            'tours/tour-1-gallery-2.jpg',
        ],
        'metadata' => [
            'video_url' => 'https://www.youtube.com/watch?v=Wep4QuMFFwU',
            'region' => 'Gedeo Zone, Southern Ethiopia',
            'elevation' => '1,950m - 2,200m MASL',
            'varieties' => 'Kurume, Dega, Wolisho (Heirloom)',
            'harvest_season' => 'November - February',
        ],
    ],
    2 => [
        'featured_image' => 'tours/tour-2-kaffa-ancient-forest-discovery.jpg',
        'gallery' => [
            'tours/tour-2-kaffa-ancient-forest-discovery.jpg',
            'tours/tour-2-gallery-1.jpg',
        ],
        'metadata' => [
            'video_url' => 'https://www.youtube.com/watch?v=7x8COYGipw8',
            'region' => 'Kaffa UNESCO Biosphere, Southwest Ethiopia',
            'elevation' => '1,600m - 1,950m MASL',
            'varieties' => 'Indigenous Wild Forest Arabica',
            'harvest_season' => 'October - January',
        ],
    ],
    3 => [
        'featured_image' => 'tours/tour-3-sidama-bensa-washing-stations.jpg',
        'gallery' => [
            'tours/tour-3-sidama-bensa-washing-stations.jpg',
            'tours/tour-3-gallery-1.jpg',
        ],
        'metadata' => [
            'video_url' => 'https://www.youtube.com/watch?v=26aHNuCGxLA',
            'region' => 'Bensa, Sidama Region',
            'elevation' => '2,000m - 2,280m MASL',
            'varieties' => '74158, 74112, 74110 & Heirloom',
            'harvest_season' => 'November - January',
        ],
    ],
    4 => [
        'featured_image' => 'tours/tour-4-harar-heritage-coffee-exchange.jpg',
        'gallery' => [
            'tours/tour-4-harar-heritage-coffee-exchange.jpg',
            'tours/tour-4-gallery-1.jpg',
        ],
        'metadata' => [
            'video_url' => 'https://www.youtube.com/watch?v=-Ij_AiEzc8Y',
            'region' => 'East Hararghe Highlands & Jugol Walled City',
            'elevation' => '1,750m - 2,100m MASL',
            'varieties' => 'Wild Harar Longberry & Shortberry',
            'harvest_season' => 'December - March',
        ],
    ],
    5 => [
        'featured_image' => 'tours/tour-5-guji-highland-coffee-safari.jpg',
        'gallery' => [
            'tours/tour-5-guji-highland-coffee-safari.jpg',
            'tours/tour-5-gallery-1.jpg',
        ],
        'metadata' => [
            'video_url' => 'https://www.youtube.com/watch?v=Xsn3yNE-XB4',
            'region' => 'Shakiso & Uraga, Guji Zone, Oromia',
            'elevation' => '2,050m - 2,350m MASL',
            'varieties' => '74110, 74112 & Wild Guji Forest Landraces',
            'harvest_season' => 'December - February',
        ],
    ],
];

foreach ($tourUpdates as $id => $data) {
    $tour = Tour::find($id);
    if ($tour) {
        $existingMeta = is_array($tour->metadata) ? $tour->metadata : [];
        $mergedMeta = array_merge($existingMeta, $data['metadata'] ?? []);
        $tour->update([
            'featured_image' => $data['featured_image'],
            'gallery' => $data['gallery'],
            'metadata' => $mergedMeta,
        ]);
        echo "Updated Tour ID {$id}: {$tour->title} | Video: " . ($mergedMeta['video_url'] ?? 'none') . PHP_EOL;
    } else {
        echo "Tour ID {$id} not found." . PHP_EOL;
    }
}

echo PHP_EOL . "=== UPDATING EVENTS WITH AUTHENTIC ETHIOPIAN ASSETS ===" . PHP_EOL;

$eventUpdates = [
    1 => [
        'featured_image' => 'events/event-1-african-coffee-conference.jpg',
        'location' => 'Millennium Hall, Bole Sub-City, Addis Ababa, Ethiopia',
        'location_description' => 'Main Exhibition Arena & B2B International Negotiation Lounge, Bole, Addis Ababa',
        'metadata' => [
            'currency' => 'ETB',
            'category' => 'International Trade Exhibition',
            'badge' => 'Major Conference',
            'video_url' => 'https://www.youtube.com/watch?v=urbK4GZ3Ric',
            'organizer' => 'African Fine Coffees Association (AFCA) & ECTA',
        ],
    ],
    2 => [
        'featured_image' => 'events/event-2-ethiopian-cup-of-excellence.jpg',
        'location' => 'ECTA National Cupping & Quality Center, Mexico Square, Addis Ababa',
        'location_description' => 'Level 3 Sensory Hall & Calibration Laboratory, Addis Ababa',
        'metadata' => [
            'currency' => 'ETB',
            'category' => 'Competition & Cupping',
            'badge' => 'Pre-Auction Tasting',
            'video_url' => 'https://www.youtube.com/watch?v=I44il3szwlA',
            'organizer' => 'Alliance for Coffee Excellence (ACE) & ECTA',
        ],
    ],
    3 => [
        'featured_image' => 'events/event-3-sca-green-coffee-grading.jpg',
        'location' => 'Fira Link Coffee Academy & Sensory Lab, Kazanchis, Addis Ababa',
        'location_description' => 'Cupping Table Suite 402, Kazanchis Commercial Hub, Addis Ababa',
        'metadata' => [
            'currency' => 'ETB',
            'category' => 'Professional Workshop',
            'badge' => 'Certified Training',
            'video_url' => 'https://www.youtube.com/watch?v=Pu9Gujy0Bas',
            'organizer' => 'Specialty Coffee Association (SCA) & Fira Link Platform',
        ],
    ],
    4 => [
        'featured_image' => 'events/event-4-kaffa-forest-biodiversity.jpg',
        'location' => 'Bonga National Coffee Museum & Research Sanctuary, Kaffa Zone, Southwest Ethiopia',
        'location_description' => 'UNESCO Kaffa Biosphere Auditorium, Bonga, Southwest Ethiopia',
        'metadata' => [
            'currency' => 'ETB',
            'category' => 'Conservation & Community',
            'badge' => 'Free Community Event',
            'video_url' => 'https://www.youtube.com/watch?v=7x8COYGipw8',
            'organizer' => 'UNESCO Biosphere Reserve & Kaffa Coffee Heritage Trust',
        ],
    ],
    5 => [
        'featured_image' => 'events/event-5-export-contracts-logistics.jpg',
        'location' => 'Addis Ababa Chamber of Commerce & Sectoral Associations (AACCSA) Hall, Mexico Square, Addis Ababa',
        'location_description' => 'Executive Trade Training Suite, Kirkos Sub-City, Addis Ababa',
        'metadata' => [
            'currency' => 'ETB',
            'category' => 'Trade & Compliance',
            'badge' => 'B2B Trade Focus',
            'video_url' => 'https://www.youtube.com/watch?v=JTUMP-AWlzU',
            'organizer' => 'Ethiopian Coffee & Tea Authority (ECTA) & Trade Ministry',
        ],
    ],
];

foreach ($eventUpdates as $id => $data) {
    $event = Event::find($id);
    if ($event) {
        $existingMeta = is_array($event->metadata) ? $event->metadata : [];
        $mergedMeta = array_merge($existingMeta, $data['metadata'] ?? []);
        $event->update([
            'featured_image' => $data['featured_image'],
            'location' => $data['location'],
            'location_description' => $data['location_description'],
            'metadata' => $mergedMeta,
        ]);
        echo "Updated Event ID {$id}: {$event->title} | Video: " . ($mergedMeta['video_url'] ?? 'none') . PHP_EOL;
    } else {
        echo "Event ID {$id} not found." . PHP_EOL;
    }
}

echo PHP_EOL . "=== SYNC COMPLETE ===" . PHP_EOL;
