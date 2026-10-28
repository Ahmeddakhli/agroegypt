<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'event', 'auditable_type', 'auditable_id', 'changes', 'ip_address'])]
#[WithoutTimestamps]
class AuditLog extends Model
{
    /** @var list<string> */
    private const HIDDEN_FIELDS = ['password', 'remember_token', 'updated_at', 'created_at', 'id'];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public static function record(string $event, Model $model, array $changes = []): void
    {
        [$type, $id] = method_exists($model, 'auditSubject') ? $model->auditSubject() : [$model->getMorphClass(), $model->getKey()];
        $changes = array_diff_key($changes, array_flip(self::HIDDEN_FIELDS));

        if ($changes === [] && $event === 'updated') {
            return; // touch()-only updates carry no information
        }

        static::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'changes' => (method_exists($model, 'auditChanges') ? $model->auditChanges($changes) : $changes) ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
