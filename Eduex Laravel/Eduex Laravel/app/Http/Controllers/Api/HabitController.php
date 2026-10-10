<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HabitController extends Controller
{
    /**
     * Get all habits with logs for the current week.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

        $habits = Habit::with([
            'logs' => function ($query) use ($startOfWeek, $endOfWeek) {
                $query->whereBetween('completed_at', [$startOfWeek, $endOfWeek]);
            }
        ])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($habit) {
                return [
                    'id' => $habit->id,
                    'name' => $habit->name,
                    'target_days_per_week' => $habit->target_days_per_week,
                    'completed_dates' => $habit->logs->pluck('completed_at')->toArray(),
                    'completion_count' => $habit->logs->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $habits,
        ]);
    }

    /**
     * Create a new habit.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_days_per_week' => 'required|integer|min:1|max:7',
        ]);

        $habit = Habit::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'target_days_per_week' => $request->target_days_per_week,
        ]);

        return response()->json([
            'success' => true,
            'data' => $habit,
        ]);
    }

    /**
     * Log a habit as completed for a specific date (toggle).
     */
    public function log(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $habit = Habit::where('user_id', $request->user()->id)->findOrFail($id);
        $date = Carbon::parse($request->date)->format('Y-m-d');

        $log = HabitLog::where('habit_id', $habit->id)
            ->where('completed_at', $date)
            ->first();

        if ($log) {
            $log->delete();
            $message = 'Habit uncompleted';
            $status = false;
        } else {
            HabitLog::create([
                'habit_id' => $habit->id,
                'completed_at' => $date,
            ]);
            $message = 'Habit completed';
            $status = true;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => $status,
        ]);
    }

    /**
     * Get weekly review stats.
     */
    public function weeklyReview(Request $request)
    {
        $user = $request->user();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // 1. Weekly completion rate
        $habits = Habit::where('user_id', $user->id)->where('is_active', true)->get();
        if ($habits->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'weekly_completion_rate' => 0,
                    'total_completed_days' => 0,
                    'missed_days' => 7,
                    'week_calendar' => [],
                    'habits_summary' => [],
                ]
            ]);
        }



        // For the "Week Calendar" row:
        $weekCalendar = [];
        $perfectDays = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');

            $completedHabitsCount = HabitLog::whereIn('habit_id', $habits->pluck('id'))
                ->where('completed_at', $dateString)
                ->count();

            // Allow some leniency or just strict all? Let's say 100% for now.
            $isCompleted = $completedHabitsCount >= $habits->count() && $habits->count() > 0;

            if ($isCompleted)
                $perfectDays++;

            $weekCalendar[] = [
                'day' => $date->format('D'),
                'date' => $date->format('d'),
                'full_date' => $dateString,
                'is_completed' => $isCompleted,
            ];
        }

        $completionRate = ($perfectDays / 7) * 100;

        // 2. Habits List with Trend
        $habitsSummary = $habits->map(function ($habit) use ($startOfWeek, $endOfWeek) {
            $currentWeekCount = HabitLog::where('habit_id', $habit->id)
                ->whereBetween('completed_at', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
                ->count();

            $lastWeekStart = $startOfWeek->copy()->subWeek();
            $lastWeekEnd = $endOfWeek->copy()->subWeek();

            $lastWeekCount = HabitLog::where('habit_id', $habit->id)
                ->whereBetween('completed_at', [$lastWeekStart->format('Y-m-d'), $lastWeekEnd->format('Y-m-d')])
                ->count();

            $trendDiff = $currentWeekCount - $lastWeekCount;
            $trend = ($trendDiff > 0 ? '+' : '') . $trendDiff;

            return [
                'id' => $habit->id,
                'name' => $habit->name,
                'completion_count' => $currentWeekCount,
                'total_days' => 7, // or $habit->target_days_per_week
                'trend' => $trend,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'weekly_completion_rate' => round($completionRate),
                'total_completed_days' => $perfectDays,
                'missed_days' => 7 - $perfectDays,
                'week_calendar' => $weekCalendar,
                'habits_summary' => $habitsSummary,
            ]
        ]);
    }
}
