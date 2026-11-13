<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'organization_id',
        'action',
        'auditable_type',
        'auditable_id',
        'before_state',
        'after_state',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function log(string $action, ?Model $model = null, array $before = [], array $after = [], ?int $orgId = null, ?int $userId = null): self
    {
        $user = auth()->user();
        return self::create([
            'user_id' => $userId ?? $user?->id,
            'organization_id' => $orgId ?? ($model instanceof Organization ? $model->id : ($model?->organization_id ?? null)),
            'action' => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'before_state' => !empty($before) ? $before : null,
            'after_state' => !empty($after) ? $after : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
