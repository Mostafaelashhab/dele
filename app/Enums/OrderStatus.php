<?php

namespace App\Enums;

/**
 * Business facing order state. Deliberately coarser than DeliveryStatus:
 * an order may span more than one delivery attempt.
 */
enum OrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public static function fromDeliveryStatus(DeliveryStatus $status): self
    {
        return match ($status) {
            DeliveryStatus::Draft => self::Draft,
            DeliveryStatus::Pending => self::Pending,
            DeliveryStatus::Delivered => self::Completed,
            DeliveryStatus::Failed, DeliveryStatus::Expired => self::Failed,
            DeliveryStatus::Cancelled => self::Cancelled,
            default => self::Active,
        };
    }

    public function label(): string
    {
        return __('order.status.'.$this->value);
    }
}
