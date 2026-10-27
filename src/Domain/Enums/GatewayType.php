<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Enums;

/**
 * Supported payment gateway identifiers.
 */
enum GatewayType: string
{
	case ASAAS = 'asaas';
	case PAGARME = 'pagarme';

	/**
	 * @return array<string>
	 */
	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}
}
