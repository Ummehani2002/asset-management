<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,name,username,email')
            ->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(function ($qry) use ($q) {
                $qry->where('description', 'like', $q)
                    ->orWhere('action', 'like', $q)
                    ->orWhere('url', 'like', $q)
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', $q)
                            ->orWhere('username', 'like', $q)
                            ->orWhere('email', 'like', $q);
                    });
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name', 'username', 'email']);
        $actions = ActivityLog::distinct()->pluck('action')->sort()->values();

        return view('activity_logs.index', compact('logs', 'users', 'actions'));
    }
}
