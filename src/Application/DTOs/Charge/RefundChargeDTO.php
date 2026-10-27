<?php

declare(strict_types=1);

namespace GatewaysSdk\Application\DTOs\Charge;

use GatewaysSdk\Domain\ValueObjects\Amount;

/**
 * Data Transfer Object for refunding a charge.
 */
readonly class RefundChargeDTO
{
	/**
	 * Constructor.
	 *
	 * @param string $chargeId Gateway charge identifier
	 * @param Amount|null $amount Partial refund amount (or null for full refund)
	 * @param string|null $reason Reason for refund
	 */
	public function __construct(
		public string $chargeId,
		public ?Amount $amount = null,
		public ?string $reason = null
	) {
	}
}
