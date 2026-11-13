<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDeviceToken;
use App\Services\FcmService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function markAsRead($id, Request $request)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    public function destroy($id, Request $request)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Register or update an FCM device token for the authenticated user.
     */
    public function storeDeviceToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token'   => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:100',
        ]);

        $token = UserDeviceToken::updateOrCreate(
            ['fcm_token' => $validated['fcm_token']],
            [
                'user_id'      => $request->user()->id,
                'device_type'  => $validated['device_type'] ?? 'android',
                'device_name'  => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully',
            'data'    => $token,
        ]);
    }

    /**
     * Remove an FCM device token on logout or unregister.
     */
    public function removeDeviceToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        UserDeviceToken::where('user_id', $request->user()->id)
            ->where('fcm_token', $validated['fcm_token'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device token removed successfully',
        ]);
    }

    /**
     * Dispatch a test push notification to verify setup.
     */
    public function testPushNotification(Request $request, FcmService $fcmService)
    {
        $user = $request->user();
        $title = $request->input('title', 'Fira Link Academy Test');
        $body  = $request->input('body', 'Push notifications are working properly on your device!');
        $data  = [
            'type' => 'test',
            'sent_at' => now()->toIso8601String(),
        ];

        $result = $fcmService->sendToUser($user, $title, $body, $data);

        return response()->json([
            'success' => true,
            'message' => 'Push notification dispatched',
            'is_fcm_configured' => $fcmService->isConfigured(),
            'result'  => $result,
        ]);
    }
}
