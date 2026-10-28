<?php

namespace App\Enums;

enum InboxStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Replied = 'replied';
    case Archived = 'archived';

    public function label(): string
    {
        return __('admin.inbox_status.'.$this->value);
    }
}
