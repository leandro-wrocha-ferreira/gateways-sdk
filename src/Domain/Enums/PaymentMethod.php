<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Enums;

/**
 * Payment methods supported across gateways.
 */
enum PaymentMethod: string
{
	case PIX = 'pix';
	case CREDIT_CARD = 'credit_card';
	case BOLETO = 'boleto';

	public function isInstant(): bool
	{
		return $this === self::PIX;
	}
}
