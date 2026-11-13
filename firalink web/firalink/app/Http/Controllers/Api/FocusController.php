<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FocusSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FocusController extends Controller
{
    /**
     * Store a new focus session.
     */
    public function store(Request $request)
    {
        $request->validate([
            'duration' => 'required|integer',
            'type' => 'required|string',
            'status' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',
        ]);

        $session = FocusSession::create([
            'user_id' => $request->user()->id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time ?? Carbon::now(),
            'duration' => $request->duration,
            'type' => $request->type,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    /**
     * Get focus stats for the user.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        // Total focus time
        $totalMinutes = FocusSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('duration');

        // Sessions today
        $sessionsToday = FocusSession::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $completedToday = FocusSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Daily average (last 7 days)
        $last7Days = FocusSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->get();

        $dailyAverage = $last7Days->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('Y-m-d');
        })->map(function ($daySessions) {
            return $daySessions->sum('duration');
        })->avg() ?? 0;

        // Streak (simplified: consecutive days with at least one completed session)
        $streak = 0;
        $checkDate = Carbon::today();

        while (true) {
            $hasSession = FocusSession::where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereDate('created_at', $checkDate)
                ->exists();

            if ($hasSession) {
                $streak++;
                $checkDate->subDay();
            } else {
                break;
            }
        }

        // Weekly progress
        $weeklyProgress = [];
        $startOfWeek = Carbon::now()->startOfWeek();

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $minutes = FocusSession::where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('duration');

            $weeklyProgress[] = [
                'day' => $date->format('D'),
                'hours' => round($minutes / 60, 1),
                'date' => $date->format('Y-m-d'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_focus_hours' => round($totalMinutes / 60, 1),
                'daily_average_hours' => round($dailyAverage / 60, 1),
                'streak' => $streak,
                'sessions_today' => $sessionsToday,
                'completed_today' => $completedToday,
                'weekly_progress' => $weeklyProgress,
            ]
        ]);
    }
}
