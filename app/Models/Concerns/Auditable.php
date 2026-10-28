<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create/update/delete events of the model in the audit log.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => AuditLog::record('created', $model, $model->getAttributes()));
        static::updated(fn (Model $model) => AuditLog::record('updated', $model, $model->getChanges()));
        static::deleted(fn (Model $model) => AuditLog::record('deleted', $model));
    }

    /**
     * The record shown as the subject of this model's log entries.
     *
     * @return array{0: string, 1: int|string|null} morph class and key
     */
    public function auditSubject(): array
    {
        return [$this->getMorphClass(), $this->getKey()];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    public function auditChanges(array $changes): array
    {
        return $changes;
    }
}
