<?php

namespace App\Support;

use App\Models\ClientComplaint;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Incident;
use App\Models\VisitorLog;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class MutableStatus
{
    public static function assertMutable(Model $model): void
    {
        if (! self::isMutable($model)) {
            throw new RuntimeException(self::lockedMessage($model));
        }
    }

    public static function isMutable(Model $model): bool
    {
        return match (true) {
            $model instanceof Incident => ! in_array($model->status, ['closed', 'rejected'], true),
            $model instanceof Expense => in_array($model->status, ['draft', 'submitted'], true),
            $model instanceof Estimate => in_array($model->status, ['draft', 'sent'], true),
            $model instanceof ClientComplaint => $model->status === 'open',
            $model instanceof VisitorLog => $model->checked_out_at === null,
            default => true,
        };
    }

    public static function lockedMessage(Model $model): string
    {
        $label = class_basename($model);

        return "{$label} cannot be edited or deleted in its current status.";
    }
}
