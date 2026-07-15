<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BudgetExpense extends Model
{
    protected $fillable = [
        'entity_budget_id',
        'submission_group_id',
        'cost_head',
        'expense_amount',
        'quantity',
        'expense_date',
        'description',
        'is_contracting',
        'amount_before_vat',
        'vat_percent',
        'vat_amount',
    ];

    protected $casts = [
        'is_contracting' => 'boolean',
        'quantity' => 'integer',
    ];

    public function entityBudget()
    {
        return $this->belongsTo(EntityBudget::class);
    }

    public static function newSubmissionGroupId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Group line items that were saved together on one form (or legacy same-second saves).
     *
     * @param  Collection<int, BudgetExpense>|iterable  $expenses
     * @return Collection<int, Collection<int, BudgetExpense>>
     */
    public static function groupBySubmission($expenses): Collection
    {
        $sorted = collect($expenses)->sortBy([
            ['expense_date', 'asc'],
            ['id', 'asc'],
        ])->values();

        $hasGroupColumn = Schema::hasColumn('budget_expenses', 'submission_group_id');
        $knownGroups = [];
        $ungrouped = collect();

        foreach ($sorted as $expense) {
            if ($hasGroupColumn && !empty($expense->submission_group_id)) {
                $gid = $expense->submission_group_id;
                if (!isset($knownGroups[$gid])) {
                    $knownGroups[$gid] = collect();
                }
                $knownGroups[$gid]->push($expense);
            } else {
                $ungrouped->push($expense);
            }
        }

        $legacyClusters = [];
        foreach ($ungrouped as $expense) {
            $attached = false;
            for ($i = count($legacyClusters) - 1; $i >= 0; $i--) {
                /** @var BudgetExpense $last */
                $last = $legacyClusters[$i]->last();
                if (
                    (int) $last->entity_budget_id === (int) $expense->entity_budget_id
                    && self::sameExpenseDate($last->expense_date, $expense->expense_date)
                    && strcasecmp(trim((string) ($last->cost_head ?? '')), trim((string) ($expense->cost_head ?? ''))) === 0
                    && self::createdWithinSeconds($last->created_at, $expense->created_at, 3)
                ) {
                    $legacyClusters[$i]->push($expense);
                    $attached = true;
                    break;
                }
            }
            if (!$attached) {
                $legacyClusters[] = collect([$expense]);
            }
        }

        return collect($knownGroups)
            ->values()
            ->concat($legacyClusters)
            ->sortBy(fn (Collection $group) => $group->min('id'))
            ->values();
    }

    private static function sameExpenseDate($a, $b): bool
    {
        if (!$a || !$b) {
            return false;
        }
        return Carbon::parse($a)->toDateString() === Carbon::parse($b)->toDateString();
    }

    private static function createdWithinSeconds($a, $b, int $seconds): bool
    {
        if (!$a || !$b) {
            return false;
        }
        return abs(Carbon::parse($a)->diffInSeconds(Carbon::parse($b), false)) <= $seconds;
    }
}
