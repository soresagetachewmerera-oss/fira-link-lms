<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Event\EventStepRequest;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Event Builder Controller
 *
 * This controller handles the event builder functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class EventBuilderController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', Event::class);

        $instructors = User::query()
            ->where('role', 'instructor')
            ->where('is_approved', true)
            ->orderBy('name')
            ->get();

        return view('instructor.events.builder.create', [
            'user' => $request->user(),
            'instructors' => $instructors,
            'steps' => $this->buildSteps(null, 'basic'),
        ]);
    }

    public function store(EventStepRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);
        $user = $request->user();

        $data = $request->validated();

        $featuredImagePath = null;
        if ($request->hasFile('featured_image')) {
            $featuredImagePath = $request->file('featured_image')->store('events/thumbnails', 'public');
        }

        $event = Event::create([
            'title' => $data['title'],
            'slug' => $this->generateUniqueSlug($data['title']),
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'location_description' => $data['location_description'] ?? null,
            'location' => $data['location'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'price' => 0,
            'is_published' => false,
            'featured_image' => $featuredImagePath,
        ]);

        $instructorIds = collect($data['instructors'] ?? [])
            ->push($user->id)
            ->unique()
            ->filter(fn ($id) => User::where('id', $id)->where('role', 'instructor')->where('is_approved', true)->exists())
            ->values();

        $event->instructors()->sync($instructorIds);

        return redirect()
            ->route('instructor.events.schedule.edit', $event)
            ->with('success', 'Basic event details saved. Continue to schedule and pricing.');
    }

    public function editBasic(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $instructors = User::query()
            ->where('role', 'instructor')
            ->where('is_approved', true)
            ->orderBy('name')
            ->get();

        return view('instructor.events.builder.create', [
            'user' => $request->user(),
            'instructors' => $instructors,
            'event' => $event->load('instructors'),
            'steps' => $this->buildSteps($event, 'basic'),
        ]);
    }

    public function updateBasic(EventStepRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $data = $request->validated();

        if ($event->title !== $data['title']) {
            $event->slug = $this->generateUniqueSlug($data['title'], $event->id);
        }

        $featuredImagePath = $event->featured_image;
        if ($request->hasFile('featured_image')) {
            if ($featuredImagePath) {
                Storage::disk('public')->delete($featuredImagePath);
            }
            $featuredImagePath = $request->file('featured_image')->store('events/thumbnails', 'public');
        }

        $event->fill([
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'location_description' => $data['location_description'] ?? null,
            'location' => $data['location'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'featured_image' => $featuredImagePath,
        ])->save();

        $instructorIds = collect($data['instructors'] ?? [])
            ->push($request->user()->id)
            ->unique()
            ->filter(fn ($id) => User::where('id', $id)->where('role', 'instructor')->where('is_approved', true)->exists())
            ->values();

        $event->instructors()->sync($instructorIds);

        return redirect()
            ->route('instructor.events.schedule.edit', $event)
            ->with('success', 'Basic event details updated.');
    }

    public function editSchedule(Request $request, Event $event): View|RedirectResponse
    {
        $this->authorize('update', $event);

        if ($redirect = $this->ensurePrerequisites($event, ['basic'])) {
            return $redirect;
        }

        return view('instructor.events.builder.schedule', [
            'event' => $event,
            'user' => $request->user(),
            'steps' => $this->buildSteps($event, 'schedule'),
        ]);
    }

    public function updateSchedule(EventStepRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $data = $request->validated();

        $event->update([
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'total_seats' => $data['total_seats'] ?? null,
            'price' => $data['price'] ?? 0,
            'contact_phone' => $data['contact_phone'] ?? $event->contact_phone,
            'contact_email' => $data['contact_email'] ?? $event->contact_email,
        ]);

        return redirect()
            ->route('instructor.events.highlights.edit', $event)
            ->with('success', 'Schedule and pricing saved. Let\'s add event highlights.');
    }

    public function editHighlights(Request $request, Event $event): View|RedirectResponse
    {
        $this->authorize('update', $event);

        if ($redirect = $this->ensurePrerequisites($event, ['basic', 'schedule'])) {
            return $redirect;
        }

        $event->load('highlights');

        return view('instructor.events.builder.highlights', [
            'event' => $event,
            'user' => $request->user(),
            'steps' => $this->buildSteps($event, 'highlights'),
        ]);
    }

    public function updateHighlights(EventStepRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $highlights = collect($request->validated()['highlights'] ?? [])
            ->filter(fn ($highlight) => trim($highlight['title'] ?? '') !== '' || trim($highlight['description'] ?? '') !== '')
            ->values();

        if ($highlights->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['highlights' => 'Please add at least one event highlight before continuing.']);
        }

        $event->highlights()->delete();

        foreach ($highlights as $index => $highlight) {
            $event->highlights()->create([
                'title' => $highlight['title'] ?? null,
                'description' => $highlight['description'] ?? null,
                'position' => $index + 1,
            ]);
        }

        return redirect()
            ->route('instructor.events.speakers.edit', $event)
            ->with('success', 'Highlights saved. Add your event speakers.');
    }

    public function editSpeakers(Request $request, Event $event): View|RedirectResponse
    {
        $this->authorize('update', $event);

        if ($redirect = $this->ensurePrerequisites($event, ['basic', 'schedule', 'highlights'])) {
            return $redirect;
        }

        $event->load('speakers');

        return view('instructor.events.builder.speakers', [
            'event' => $event,
            'user' => $request->user(),
            'steps' => $this->buildSteps($event, 'speakers'),
        ]);
    }

    public function updateSpeakers(EventStepRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $speakers = collect($request->validated()['speakers'] ?? [])
            ->filter(fn ($speaker) => trim($speaker['name'] ?? '') !== '')
            ->values();

        if ($speakers->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['speakers' => 'Please add at least one speaker before continuing.']);
        }

        foreach ($speakers as $index => $speaker) {
            $existingImage = $speaker['existing_image'] ?? null;
            $file = $request->file("speakers.$index.image");

            if (! $file && ! $existingImage) {
                return back()
                    ->withInput()
                    ->withErrors(["speakers.$index.image" => 'Please upload a headshot for this speaker.']);
            }
        }

        $event->speakers()->delete();

        foreach ($speakers as $index => $speaker) {
            $existingImage = $speaker['existing_image'] ?? null;
            $file = $request->file("speakers.$index.image");
            $imagePath = $existingImage;

            if ($file) {
                if ($existingImage) {
                    Storage::disk('public')->delete($existingImage);
                }
                $imagePath = $file->store('events/speakers', 'public');
            }

            $event->speakers()->create([
                'name' => $speaker['name'],
                'title' => $speaker['title'] ?? null,
                'bio' => $speaker['bio'] ?? null,
                'email' => $speaker['email'] ?? null,
                'phone' => $speaker['phone'] ?? null,
                'image_path' => $imagePath,
                'position' => $index + 1,
            ]);
        }

        return redirect()
            ->route('instructor.events.review.edit', $event)
            ->with('success', 'Speakers saved. Review your event before publishing.');
    }

    public function editReview(Request $request, Event $event): View|RedirectResponse
    {
        $this->authorize('update', $event);

        if ($redirect = $this->ensurePrerequisites($event, ['basic', 'schedule', 'highlights', 'speakers'])) {
            return $redirect;
        }

        $event->load(['highlights', 'speakers', 'instructors']);

        return view('instructor.events.builder.review', [
            'event' => $event,
            'user' => $request->user(),
            'steps' => $this->buildSteps($event, 'review'),
        ]);
    }

    public function publish(EventStepRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        if ($redirect = $this->ensurePrerequisites($event, ['basic', 'schedule', 'highlights', 'speakers'])) {
            return $redirect;
        }

        $event->update([
            'is_published' => (bool) $request->validated()['is_published'],
        ]);

        $message = $event->is_published
            ? 'Event published successfully!'
            : 'Event saved as draft.';

        return redirect()
            ->route('instructor.events.index')
            ->with('success', $message);
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while (Event::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * @return array<int, array{key:string,label:string,completed:bool,active:bool,locked:bool,url:?string}>
     */
    private function buildSteps(?Event $event, string $currentStep): array
    {
        $labels = [
            'basic' => 'Basics',
            'schedule' => 'Schedule',
            'highlights' => 'Highlights',
            'speakers' => 'Speakers',
            'review' => 'Review',
        ];

        $order = array_keys($labels);
        $completion = $event ? $this->completionStatus($event) : [];
        $steps = [];
        $previousComplete = true;

        foreach ($order as $step) {
            $hasEvent = (bool) $event;
            $stepCompleted = $completion[$step] ?? false;
            $isActive = $step === $currentStep;
            $url = $this->stepUrl($event, $step);
            $locked = false;

            if ($step !== 'basic') {
                if (! $hasEvent || ! $previousComplete) {
                    $locked = ! $isActive;
                    if ($locked) {
                        $url = null;
                    }
                }
            }

            $steps[] = [
                'key' => $step,
                'label' => $labels[$step],
                'completed' => $stepCompleted,
                'active' => $isActive,
                'locked' => $locked,
                'url' => $url,
            ];

            if ($step !== 'review') {
                $previousComplete = $previousComplete && ($completion[$step] ?? false);
            }
        }

        return $steps;
    }

    private function stepUrl(?Event $event, string $step): ?string
    {
        return match ($step) {
            'basic' => $event ? route('instructor.events.basic.edit', $event) : route('instructor.events.create'),
            'schedule' => $event ? route('instructor.events.schedule.edit', $event) : null,
            'highlights' => $event ? route('instructor.events.highlights.edit', $event) : null,
            'speakers' => $event ? route('instructor.events.speakers.edit', $event) : null,
            'review' => $event ? route('instructor.events.review.edit', $event) : null,
            default => null,
        };
    }

    /**
     * @return array<string,bool>
     */
    private function completionStatus(Event $event): array
    {
        $basicComplete = filled($event->title) && filled($event->description) && filled($event->featured_image);
        $scheduleComplete = (bool) $event->start_date;
        $highlightsComplete = $event->highlights()->exists();
        $speakersCount = $event->speakers()->count();
        $speakersWithPhotos = $event->speakers()->whereNull('image_path')->count() === 0;
        $speakersComplete = $speakersCount > 0 && $speakersWithPhotos;

        return [
            'basic' => $basicComplete,
            'schedule' => $scheduleComplete,
            'highlights' => $highlightsComplete,
            'speakers' => $speakersComplete,
            'review' => $basicComplete && $scheduleComplete && $highlightsComplete && $speakersComplete,
        ];
    }

    private function ensurePrerequisites(Event $event, array $requiredSteps): ?RedirectResponse
    {
        $completion = $this->completionStatus($event);

        foreach ($requiredSteps as $step) {
            if (! ($completion[$step] ?? false)) {
                $route = $this->stepUrl($event, $step) ?? route('instructor.events.create');

                return redirect()->to($route)
                    ->with('error', 'Please complete the ' . ucfirst($step) . ' step before continuing.');
            }
        }

        return null;
    }
}
