<?php

namespace App\Http\Controllers;

use App\Models\TimeManagement;
use App\Models\User;
use App\Models\WorkTicket;
use App\Rules\AllowedEmailDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WorkLogAppController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('worklog.create');
        }

        return view('work_log_app.login');
    }

    public function manifest()
    {
        $base = rtrim((string) config('app.url'), '/');

        return response()->json([
            'name' => 'Tanseeq Work Log',
            'short_name' => 'Work Log',
            'description' => 'Log daily work tasks and time for Tanseeq employees',
            'start_url' => $base.'/work-log-app/create',
            'scope' => $base.'/work-log-app/',
            'id' => $base.'/work-log-app/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#F4F6F9',
            'theme_color' => '#1F2A44',
            'icons' => [
                [
                    'src' => $base.'/images/work-log-icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $base.'/images/work-log-icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('worklog.create');
        }

        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        $user = User::whereRaw('LOWER(username) = ?', [strtolower($username)])->first()
            ?? User::whereRaw('LOWER(email) = ?', [strtolower($username)])->first();

        if ($user && Hash::check($password, $user->password)) {
            if (! AllowedEmailDomain::isAllowed($user->email)) {
                return back()->withErrors(['username' => AllowedEmailDomain::rejectionMessage()]);
            }

            Auth::login($user);

            return redirect()->route('worklog.create');
        }

        return back()->withErrors(['username' => 'Invalid username or password.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('worklog.login');
    }

    public function index(Request $request)
    {
        try {
            if (! Schema::hasTable('time_managements')) {
                return view('work_log_app.index', [
                    'tasks' => collect(),
                    'isAdmin' => Auth::user()?->isTimeManagementAdmin() ?? false,
                    'teamMembers' => collect(),
                    'stats' => ['total' => 0, 'pending' => 0, 'completed' => 0, 'today' => 0, 'hours_today' => 0],
                    'dailySummaries' => [],
                    'summaryDate' => today()->format('Y-m-d'),
                    'ticketSummaries' => [],
                'dailySummaryTotals' => ['total_hours' => 0, 'overtime_hours' => 0, 'employee_count' => 0, 'active_count' => 0],
                ])->with('warning', 'Database tables not found. Please run migrations.');
            }

            $user = Auth::user();
            $isAdmin = $user->isTimeManagementAdmin();

            $query = TimeManagement::query()
                ->with('workTicket')
                ->orderByDesc('job_card_date')
                ->orderByDesc('start_time');

            if ($isAdmin) {
                if ($request->filled('user_id')) {
                    $query->where('user_id', $request->user_id);
                }
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if ($user->employee_id) {
                        $q->orWhere('employee_id', $user->employee_id);
                    }
                });
            }

            if ($request->filled('status') && in_array($request->status, ['pending', 'completed'], true)) {
                $query->where('status', $request->status);
            }

            $tasks = $query->get();

            foreach ($tasks->unique(fn ($task) => ($task->user_id ?? 0) . '|' . $task->job_card_date?->format('Y-m-d')) as $task) {
                if ($task->job_card_date) {
                    TimeManagement::recalculateDailyOvertime(
                        $task->employee_id,
                        $task->user_id,
                        $task->job_card_date->format('Y-m-d')
                    );
                }
            }

            if ($tasks->isNotEmpty()) {
                $tasks = TimeManagement::whereIn('id', $tasks->pluck('id'))
                    ->orderByDesc('job_card_date')
                    ->orderByDesc('start_time')
                    ->get();
            }

            $statsQuery = TimeManagement::query();
            if (! $isAdmin) {
                $statsQuery->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if ($user->employee_id) {
                        $q->orWhere('employee_id', $user->employee_id);
                    }
                });
            } elseif ($request->filled('user_id')) {
                $statsQuery->where('user_id', $request->user_id);
            }

            $todayTotals = TimeManagement::getDailyTotals($user->id, $user->employee_id, today()->format('Y-m-d'));

            $stats = [
                'total' => (clone $statsQuery)->count(),
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
                'today' => (clone $statsQuery)->whereDate('job_card_date', today())->count(),
                'hours_today' => $todayTotals['total_hours'],
            ];

            $teamMembers = $isAdmin
                ? User::orderBy('name')->get(['id', 'name'])
                : collect();

            $summaryDate = $request->input('summary_date', today()->format('Y-m-d'));
            $dailySummaries = $isAdmin
                ? TimeManagement::getAdminDailySummaries(
                    $summaryDate,
                    $request->filled('user_id') ? (int) $request->user_id : null,
                    $teamMembers
                )
                : [];
            $dailySummaryTotals = $isAdmin
                ? TimeManagement::summarizeDailyTotals($dailySummaries)
                : ['total_hours' => 0, 'overtime_hours' => 0, 'employee_count' => 0, 'active_count' => 0];

            if ($isAdmin) {
                $stats['hours_today'] = $dailySummaryTotals['total_hours'];
                $stats['team_active_today'] = $dailySummaryTotals['active_count'];
            }

            $ticketSummaries = $isAdmin
                ? WorkTicket::adminTicketSummaries(
                    $request->filled('user_id') ? (int) $request->user_id : null,
                    $request->filled('status') ? $request->status : null
                )
                : [];

            return view('work_log_app.index', compact('tasks', 'isAdmin', 'teamMembers', 'stats', 'dailySummaries', 'summaryDate', 'ticketSummaries', 'dailySummaryTotals'));
        } catch (\Exception $e) {
            Log::error('WorkLogApp index error: ' . $e->getMessage());

            return view('work_log_app.index', [
                'tasks' => collect(),
                'isAdmin' => Auth::user()?->isTimeManagementAdmin() ?? false,
                'teamMembers' => collect(),
                'stats' => ['total' => 0, 'pending' => 0, 'completed' => 0, 'today' => 0, 'hours_today' => 0],
                'dailySummaries' => [],
                'summaryDate' => today()->format('Y-m-d'),
                'ticketSummaries' => [],
                'dailySummaryTotals' => ['total_hours' => 0, 'overtime_hours' => 0, 'employee_count' => 0, 'active_count' => 0],
            ])->with('warning', 'Unable to load work logs.');
        }
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->isTimeManagementAdmin();
        $todayTotals = TimeManagement::getDailyTotals($user->id, $user->employee_id, date('Y-m-d'));
        $openTickets = $isAdmin ? collect() : WorkTicket::openTicketsForUser($user);
        $continueTicket = null;

        if (! $isAdmin && $request->filled('work_ticket_id')) {
            $continueTicket = WorkTicket::find($request->work_ticket_id);
            if ($continueTicket && ! $continueTicket->belongsToUser($user)) {
                $continueTicket = null;
            }
        }

        $todayJobs = TimeManagement::query()
            ->whereDate('job_card_date', today())
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->employee_id) {
                    $q->orWhere('employee_id', $user->employee_id);
                }
            })
            ->orderByDesc('start_time')
            ->get();

        return view('work_log_app.create', [
            'employeeName' => $user->name,
            'defaultCategory' => TimeManagement::DEFAULT_CATEGORY,
            'todayTotals' => $todayTotals,
            'todayJobs' => $todayJobs,
            'isAdmin' => $isAdmin,
            'openTickets' => $openTickets,
            'continueTicket' => $continueTicket,
        ]);
    }

    public function edit($id)
    {
        $record = TimeManagement::findOrFail($id);

        if (! $record->isOwnedBy(Auth::user())) {
            abort(403);
        }

        $user = Auth::user();
        $date = optional($record->job_card_date)->format('Y-m-d') ?? date('Y-m-d');
        $todayTotals = TimeManagement::getDailyTotals($user->id, $user->employee_id, $date, $record->id);

        return view('work_log_app.edit', [
            'record' => $record->load('workTicket'),
            'todayTotals' => $todayTotals,
            'isAdmin' => $user->isTimeManagementAdmin(),
            'openTickets' => $user->isTimeManagementAdmin() ? collect() : WorkTicket::openTicketsForUser($user),
        ]);
    }
}
