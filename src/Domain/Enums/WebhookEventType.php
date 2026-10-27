<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Enums;

/**
 * Normalized webhook event types across gateways.
 */
enum WebhookEventType: string
{
	case PAYMENT_CREATED = 'payment.created';
	case PAYMENT_PAID = 'payment.paid';
	case PAYMENT_FAILED = 'payment.failed';
	case PAYMENT_OVERDUE = 'payment.overdue';
	case PAYMENT_REFUNDED = 'payment.refunded';
	case PAYMENT_CANCELLED = 'payment.cancelled';
	case UNKNOWN = 'unknown';
}
