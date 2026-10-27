<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Enums;

/**
 * Normalized payment statuses across gateways.
 */
enum PaymentStatus: string
{
	case PENDING = 'pending';
	case AUTHORIZED = 'authorized';
	case PAID = 'paid';
	case FAILED = 'failed';
	case CANCELLED = 'cancelled';
	case REFUNDED = 'refunded';
	case PARTIALLY_REFUNDED = 'partially_refunded';
	case OVERDUE = 'overdue';

	public function isFinal(): bool
	{
		return in_array($this, [self::PAID, self::FAILED, self::CANCELLED, self::REFUNDED], true);
	}
}
