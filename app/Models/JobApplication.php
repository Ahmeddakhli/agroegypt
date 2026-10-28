<?php

namespace App\Models;

use App\Enums\InboxStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'email', 'phone', 'job_title', 'message', 'cv_path', 'cv_original_name', 'locale', 'status', 'read_at', 'notes', 'ip_address'])]
class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => InboxStatus::class,
            'read_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function unread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        $this->forceFill(['read_at' => now()])->save();
    }

    public function markAsUnread(): void
    {
        $this->forceFill(['read_at' => null])->save();
    }
}
