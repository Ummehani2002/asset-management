@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2 class="mb-0"><i class="bi bi-journal-text me-2"></i>User Activity Logs</h2>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All users</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name ?? $u->username ?? $u->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($actions as $a)
                            <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Description, URL, user..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>URL / Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at->format('d-M-Y H:i:s') }}</td>
                                <td>
                                    @if($log->user)
                                        {{ $log->user->name ?? $log->user->username ?? $log->user->email }}
                                        <small class="text-muted d-block">{{ $log->user->email }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                                <td>{{ Str::limit($log->description, 80) }}</td>
                                <td>
                                    <small>{{ $log->method }} {{ Str::limit($log->url, 50) }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No activity logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
