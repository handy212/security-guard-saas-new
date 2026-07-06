<?php

namespace App\Enums;

enum ConfirmationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
}
