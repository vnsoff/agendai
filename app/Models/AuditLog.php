<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed>|null $changes
 * @property string|null $ip
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * Record an action by the current user in the current tenant.
     *
     * @param  array<string, mixed>  $changes  usually ['field' => ['from' => …, 'to' => …]]
     */
    public static function record(string $action, ?Model $subject = null, array $changes = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'changes' => $changes ?: null,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Field-by-field diff of a model's pending changes, for record().
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public static function diff(Model $model): array
    {
        $diff = [];

        foreach ($model->getDirty() as $field => $to) {
            $diff[$field] = ['from' => $model->getOriginal($field), 'to' => $to];
        }

        return $diff;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
