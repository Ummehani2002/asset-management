<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeManagement extends Model
{
    use HasFactory;

    public const DAILY_STANDARD_HOURS = 8;

    public const DEFAULT_CATEGORY = 'End User Support';

    protected $table = 'time_managements';

    protected $fillable = [
        'ticket_number',
        'work_ticket_id',
        'category',
        'task_description',
        'site_location',
        'user_id',
        'employee_id',
        'employee_name',
        'project_name',
        'job_card_date',
        'standard_man_hours',
        'start_time',
        'end_time',
        'duration_hours',
        'overtime_hours',
        'action_taken',
        'remarks',
        'status',
        'delayed_days',
        'delay_reason',
        'performance_percent',
        'last_delay_email_sent_at',
    ];

    protected $casts = [
        'job_card_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'last_delay_email_sent_at' => 'datetime',
        'duration_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'standard_man_hours' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function workTicket()
    {
        return $this->belongsTo(WorkTicket::class);
    }

    public function ticketStatus(): string
    {
        if ($this->workTicket) {
            return $this->workTicket->status === 'completed' ? 'completed' : 'pending';
        }

        return $this->status === 'in_progress' ? 'pending' : ($this->status ?? 'pending');
    }

    public static function generateTicketNumber(): string
    {
        $lastRecord = self::orderBy('id', 'desc')->first();
        $lastNumber = $lastRecord && $lastRecord->ticket_number
            ? (int) preg_replace('/\D/', '', $lastRecord->ticket_number)
            : 0;

        return 'TCKT' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function calculateDurationHours(Carbon $start, Carbon $end): float
    {
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        return round(abs($start->diffInMinutes($end)) / 60, 2);
    }

    /**
     * Human-readable duration, e.g. 0.5 → "30 min", 1.5 → "1 hr 30 min".
     */
    public static function formatDuration(float|int|string|null $hours): string
    {
        $hours = (float) ($hours ?? 0);
        if ($hours <= 0) {
            return '0 min';
        }

        $totalMinutes = (int) round($hours * 60);
        $hrs = intdiv($totalMinutes, 60);
        $mins = $totalMinutes % 60;

        if ($hrs === 0) {
            return $mins.' min';
        }

        if ($mins === 0) {
            return $hrs === 1 ? '1 hr' : $hrs.' hrs';
        }

        $hrPart = $hrs === 1 ? '1 hr' : $hrs.' hrs';

        return $hrPart.' '.$mins.' min';
    }

    /**
     * Recalculate duration and overtime for all entries of a user on a given date.
     * First 8 hours of the day are regular; excess is overtime.
     */
    public static function getDailyTotals(?int $userId, ?int $employeeId, string $date, ?int $excludeId = null): array
    {
        $query = self::whereDate('job_card_date', $date)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($employeeId) {
            $query->where('employee_id', $employeeId);
        } else {
            return ['total_hours' => 0.0, 'overtime_hours' => 0.0, 'job_count' => 0];
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $entries = $query->get();

        return [
            'total_hours' => round((float) $entries->sum('duration_hours'), 2),
            'overtime_hours' => round((float) $entries->sum('overtime_hours'), 2),
            'job_count' => $entries->count(),
        ];
    }

    /**
     * Daily totals grouped by employee for admin dashboards.
     *
     * @param  iterable<int, \App\Models\User>|null  $teamMembers
     * @return array<int, array{user_id: int|null, employee_name: string, total_hours: float, overtime_hours: float, job_count: int}>
     */
    public static function getAdminDailySummaries(string $date, ?int $filterUserId = null, ?iterable $teamMembers = null): array
    {
        $dayQuery = self::whereDate('job_card_date', $date)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        if ($filterUserId) {
            $dayQuery->where('user_id', $filterUserId);
        }

        foreach ($dayQuery->get()->unique(fn ($entry) => ($entry->user_id ?? 0).'|'.$date) as $entry) {
            self::recalculateDailyOvertime($entry->employee_id, $entry->user_id, $date);
        }

        $query = self::whereDate('job_card_date', $date)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        if ($filterUserId) {
            $query->where('user_id', $filterUserId);
        }

        $summaries = [];

        if ($teamMembers && ! $filterUserId) {
            foreach ($teamMembers as $member) {
                $summaries[(string) $member->id] = [
                    'user_id' => $member->id,
                    'employee_name' => $member->name,
                    'total_hours' => 0.0,
                    'overtime_hours' => 0.0,
                    'job_count' => 0,
                ];
            }
        }

        foreach ($query->get() as $entry) {
            $key = (string) ($entry->user_id ?? ('emp-' . $entry->employee_id));

            if (! isset($summaries[$key])) {
                $summaries[$key] = [
                    'user_id' => $entry->user_id,
                    'employee_name' => $entry->employee_name ?? 'Unknown',
                    'total_hours' => 0.0,
                    'overtime_hours' => 0.0,
                    'job_count' => 0,
                ];
            }

            $summaries[$key]['total_hours'] += (float) ($entry->duration_hours ?? 0);
            $summaries[$key]['overtime_hours'] += (float) ($entry->overtime_hours ?? 0);
            $summaries[$key]['job_count']++;
        }

        foreach ($summaries as &$summary) {
            $summary['total_hours'] = round($summary['total_hours'], 2);
            $summary['overtime_hours'] = round($summary['overtime_hours'], 2);
        }
        unset($summary);

        usort($summaries, fn ($a, $b) => strcmp($a['employee_name'], $b['employee_name']));

        return array_values($summaries);
    }

    /**
     * @param  array<int, array{total_hours?: float, overtime_hours?: float}>  $summaries
     * @return array{total_hours: float, overtime_hours: float, employee_count: int, active_count: int}
     */
    public static function summarizeDailyTotals(array $summaries): array
    {
        $totalHours = 0.0;
        $overtimeHours = 0.0;
        $activeCount = 0;

        foreach ($summaries as $summary) {
            $hours = (float) ($summary['total_hours'] ?? 0);
            $totalHours += $hours;
            $overtimeHours += (float) ($summary['overtime_hours'] ?? 0);
            if ($hours > 0) {
                $activeCount++;
            }
        }

        return [
            'total_hours' => round($totalHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'employee_count' => count($summaries),
            'active_count' => $activeCount,
        ];
    }

    public static function recalculateDailyOvertime(?int $employeeId, ?int $userId, string $date): void
    {
        $query = self::whereDate('job_card_date', $date)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($employeeId) {
            $query->where('employee_id', $employeeId);
        } else {
            return;
        }

        $entries = $query->orderBy('start_time')->orderBy('id')->get();

        foreach ($entries as $entry) {
            $entry->duration_hours = self::calculateDurationHours(
                Carbon::parse($entry->start_time),
                Carbon::parse($entry->end_time)
            );
        }

        $regularUsed = 0.0;

        foreach ($entries as $entry) {
            $duration = max(0, (float) ($entry->duration_hours ?? 0));
            $regularForEntry = min($duration, max(0, self::DAILY_STANDARD_HOURS - $regularUsed));
            $overtimeForEntry = max(0, $duration - $regularForEntry);

            $regularUsed += $regularForEntry;

            $entry->standard_man_hours = self::DAILY_STANDARD_HOURS;
            $entry->overtime_hours = round($overtimeForEntry, 2);
            $entry->saveQuietly();
        }
    }

    public function isOwnedBy(User $user): bool
    {
        if ($user->isTimeManagementAdmin()) {
            return true;
        }

        if ($this->user_id && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        return $user->employee_id && (int) $this->employee_id === (int) $user->employee_id;
    }
}
