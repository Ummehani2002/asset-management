<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'url',
        'method',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Log an activity. Call from controllers/middleware.
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?string $subjectType = null,
        $subjectId = null,
        array $properties = []
    ): ?self {
        try {
            $request = request();
            $userId = auth()->id();

            if ($userId !== null && ! User::whereKey($userId)->exists()) {
                $userId = null;
            }

            return self::create([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'properties' => $properties ?: null,
                'url' => $request ? $request->fullUrl() : null,
                'method' => $request ? $request->method() : null,
                'ip' => $request ? $request->ip() : null,
                'user_agent' => $request ? $request->userAgent() : null,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Activity log failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
