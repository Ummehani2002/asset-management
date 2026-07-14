<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class WorkTicket extends Model
{
    protected $fillable = [
        'ticket_number',
        'user_id',
        'employee_id',
        'employee_name',
        'category',
        'task_description',
        'site_location',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(TimeManagement::class, 'work_ticket_id')
            ->orderBy('job_card_date')
            ->orderBy('start_time');
    }

    public function isOpen(): bool
    {
        return $this->status !== 'completed';
    }

    public function totalDurationHours(): float
    {
        return round((float) $this->visits()->sum('duration_hours'), 2);
    }

    public function visitCount(): int
    {
        return (int) $this->visits()->count();
    }

    public function firstVisitDate(): ?string
    {
        $date = $this->visits()->min('job_card_date');

        return $date ? (string) $date : null;
    }

    public function lastVisitDate(): ?string
    {
        $date = $this->visits()->max('job_card_date');

        return $date ? (string) $date : null;
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->visits()->where('status', '!=', 'completed')->update(['status' => 'completed']);
    }

    public function isOwnedBy(User $user): bool
    {
        if ($user->isTimeManagementAdmin()) {
            return true;
        }

        return $this->belongsToUser($user);
    }

    /**
     * True when this ticket belongs to the employee (not just admin access).
     */
    public function belongsToUser(User $user): bool
    {
        if ($this->user_id && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        return $user->employee_id && (int) $this->employee_id === (int) $user->employee_id;
    }

    public static function openTicketsForUser(User $user)
    {
        if (! Schema::hasTable('work_tickets')) {
            return collect();
        }

        // Open tickets are for employees to continue their own work only.
        if ($user->isTimeManagementAdmin()) {
            return collect();
        }

        self::syncFromPendingLogs($user);

        return self::query()
            ->where('status', 'pending')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->employee_id) {
                    $q->orWhere('employee_id', $user->employee_id);
                }
            })
            ->withCount('visits')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Ensure older pending work logs become open tickets that can be continued.
     */
    public static function syncFromPendingLogs(User $user): void
    {
        if (! Schema::hasTable('work_tickets') || ! Schema::hasTable('time_managements')) {
            return;
        }

        $logs = TimeManagement::query()
            ->whereNotNull('ticket_number')
            ->where('ticket_number', '!=', '')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', 'pending')
                    ->orWhere('status', 'in_progress');
            })
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->employee_id) {
                    $q->orWhere('employee_id', $user->employee_id);
                }
            })
            ->where(function ($q) {
                $q->whereNull('work_ticket_id')->orWhere('work_ticket_id', 0);
            })
            ->orderBy('id')
            ->get();

        foreach ($logs->groupBy(fn ($log) => strtolower(trim((string) $log->ticket_number))) as $ticketNumber => $group) {
            if ($ticketNumber === '') {
                continue;
            }

            $first = $group->first();
            $ticket = self::query()
                ->where('status', 'pending')
                ->whereRaw('LOWER(ticket_number) = ?', [$ticketNumber])
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if ($user->employee_id) {
                        $q->orWhere('employee_id', $user->employee_id);
                    }
                })
                ->first();

            if (! $ticket) {
                $ticket = self::create([
                    'ticket_number' => $first->ticket_number,
                    'user_id' => $first->user_id ?? $user->id,
                    'employee_id' => $first->employee_id ?? $user->employee_id,
                    'employee_name' => $first->employee_name ?? $user->name,
                    'category' => $first->category ?? TimeManagement::DEFAULT_CATEGORY,
                    'task_description' => $first->task_description ?? 'Work log',
                    'site_location' => $first->site_location ?? 'N/A',
                    'status' => 'pending',
                ]);
            }

            TimeManagement::whereIn('id', $group->pluck('id'))
                ->update(['work_ticket_id' => $ticket->id]);
        }
    }

    public static function findOpenForUser(User $user, string $ticketNumber): ?self
    {
        if (! Schema::hasTable('work_tickets')) {
            return null;
        }

        $ticketNumber = trim($ticketNumber);
        if ($ticketNumber === '') {
            return null;
        }

        return self::query()
            ->where('status', 'pending')
            ->whereRaw('LOWER(ticket_number) = ?', [strtolower($ticketNumber)])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->employee_id) {
                    $q->orWhere('employee_id', $user->employee_id);
                }
            })
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function adminTicketSummaries(?int $filterUserId = null, ?string $status = null): array
    {
        if (! Schema::hasTable('work_tickets')) {
            return [];
        }

        $query = self::query()->with(['visits' => function ($q) {
            $q->orderBy('job_card_date')->orderBy('start_time');
        }]);

        if ($filterUserId) {
            $query->where('user_id', $filterUserId);
        }

        if ($status && in_array($status, ['pending', 'completed'], true)) {
            $query->where('status', $status);
        }

        $summaries = [];

        foreach ($query->orderByDesc('updated_at')->get() as $ticket) {
            $totalHours = round((float) $ticket->visits->sum('duration_hours'), 2);
            $summaries[] = [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'employee_name' => $ticket->employee_name ?? 'Unknown',
                'category' => $ticket->category,
                'task_description' => $ticket->task_description,
                'site_location' => $ticket->site_location,
                'status' => $ticket->status,
                'visit_count' => $ticket->visits->count(),
                'total_hours' => $totalHours,
                'first_visit' => $ticket->visits->min('job_card_date'),
                'last_visit' => $ticket->visits->max('job_card_date'),
            ];
        }

        return $summaries;
    }
}
