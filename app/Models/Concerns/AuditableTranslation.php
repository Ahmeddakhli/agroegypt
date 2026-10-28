<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * For "{Parent}Translation" models: log entries are attached to the parent record (e.g. the Page) and
 * carry the language, so translation-only edits appear in the parent's history.
 */
trait AuditableTranslation
{
    use Auditable;

    /**
     * @return array{0: string, 1: int|string|null}
     */
    public function auditSubject(): array
    {
        $parent = Str::beforeLast(class_basename(static::class), 'Translation');
        $class = 'App\\Models\\'.$parent;

        return [(new $class)->getMorphClass(), $this->getAttribute(Str::snake($parent).'_id')];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    public function auditChanges(array $changes): array
    {
        return ['locale' => $this->getAttribute('locale'), ...$changes];
    }
}
