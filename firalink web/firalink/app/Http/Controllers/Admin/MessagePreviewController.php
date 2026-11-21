<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\Catalog\NotificationType;
use App\Services\Notifications\MessagePreview;
use Illuminate\Http\Request;

/**
 * Admin preview of every catalogue message (messaging slice 2): email HTML and plain text, SMS with
 * its segment count, push and the in-app row, in English or Amharic, with sample data. Admins only
 * (role admin + staff two-factor). Nothing is sent.
 */
class MessagePreviewController extends Controller
{
    public function __construct(private MessagePreview $preview)
    {
    }

    public function index()
    {
        $groups = [];
        foreach (NotificationType::byGroup() as $group => $types) {
            foreach ($types as $type) {
                $groups[$group][] = [
                    'type' => $type,
                    'class' => $type->messageClass()->value,
                    'templates' => $this->preview->templates($type),
                ];
            }
        }

        return view('admin.messages.index', ['groups' => $groups]);
    }

    public function show(Request $request, string $type)
    {
        $t = NotificationType::tryFrom($type) ?? abort(404);
        $locale = in_array($request->query('locale'), ['en', 'am'], true) ? $request->query('locale') : 'en';

        return match ($request->query('view', 'html')) {
            'html' => response($this->preview->mailHtml($t, $locale)),
            'text' => response($this->preview->mailText($t, $locale), 200, ['Content-Type' => 'text/plain; charset=UTF-8']),
            'sms' => response()->json($this->preview->sms($t, $locale)),
            'push' => response()->json($this->preview->push($t, $locale)),
            'inapp' => response()->json($this->preview->inApp($t)),
            default => abort(404),
        };
    }
}
