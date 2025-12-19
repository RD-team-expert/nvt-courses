<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupLearningTimeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BackupCalculationReportController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('learning_sessions as ls')
            ->join('users as u', 'ls.user_id', '=', 'u.id')
            ->join('course_online as co', 'ls.course_online_id', '=', 'co.id')
            ->select(
                'ls.user_id',
                'u.name as user_name',
                'ls.course_online_id',
                'co.title as course_title',
                DB::raw('COUNT(ls.id) as total_sessions'),
                DB::raw('SUM(CASE WHEN ls.total_duration_minutes = 0 AND ls.active_playback_time = 0 THEN 1 ELSE 0 END) as sessions_needing_backup'),
                DB::raw('MAX(ls.session_start) as last_session')
            )
            ->groupBy('ls.user_id', 'u.name', 'ls.course_online_id', 'co.title')
            ->having('sessions_needing_backup', '>', 0)
            ->orderBy('last_session', 'desc');

        $usersNeedingBackup = $query->paginate(20);

        // Add backup calculation for each user
        $calculator = new BackupLearningTimeCalculator();
        
        foreach ($usersNeedingBackup as $user) {
            $backupData = $calculator->calculateBackupTime($user->user_id, $user->course_online_id);
            $user->backup_minutes = $backupData['total_minutes'];
            $user->backup_strategy = $backupData['strategy_used'] ?? 'none';
            $user->needs_backup_percentage = round(($user->sessions_needing_backup / $user->total_sessions) * 100, 1);
        }

        return Inertia::render('Admin/Reports/BackupCalculation', [
            'users' => $usersNeedingBackup,
            'stats' => [
                'total_users_affected' => $query->count(),
                'total_sessions_needing_backup' => DB::table('learning_sessions')
                    ->where('total_duration_minutes', 0)
                    ->where('active_playback_time', 0)
                    ->count(),
            ]
        ]);
    }

    public function show(Request $request, $userId, $courseId)
    {
        $calculator = new BackupLearningTimeCalculator();
        $report = $calculator->getDetailedReport($userId, $courseId);

        return response()->json($report);
    }
}
