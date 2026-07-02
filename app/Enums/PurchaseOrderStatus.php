<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case ORDERED = 'ordered';
    case PARTIAL = 'partial';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';
}
