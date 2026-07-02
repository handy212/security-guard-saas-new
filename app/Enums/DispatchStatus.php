<?php

namespace App\Enums;

enum DispatchStatus: string
{
    case OPEN = 'open';
    case ASSIGNED = 'assigned';
    case EN_ROUTE = 'en_route';
    case ON_SCENE = 'on_scene';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function next(): ?self
    {
        return match ($this) {
            self::OPEN => self::ASSIGNED,
            self::ASSIGNED => self::EN_ROUTE,
            self::EN_ROUTE => self::ON_SCENE,
            self::ON_SCENE => self::RESOLVED,
            self::RESOLVED => self::CLOSED,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::ASSIGNED => 'Assigned',
            self::EN_ROUTE => 'En route',
            self::ON_SCENE => 'On scene',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
