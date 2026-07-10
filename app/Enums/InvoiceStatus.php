<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PART_PAID = 'partial';
    case PAID = 'paid';
    case VOID = 'void';
    case OVERDUE = 'overdue';
}
