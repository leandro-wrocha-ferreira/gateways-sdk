<?php

declare(strict_types=1);

namespace GatewaysSdk\Application\DTOs\Charge;

use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\ValueObjects\Amount;

/**
 * Result of a refund operation.
 */
readonly class RefundResultDTO
{
	/**
	 * Constructor.
	 *
	 * @param string $refundId Refund identifier
	 * @param string $chargeId Related charge identifier
	 * @param PaymentStatus $status Refund status
	 * @param Amount $amount Refunded amount
	 * @param array<string, mixed> $rawResponse Raw gateway payload
	 */
	public function __construct(
		public string $refundId,
		public string $chargeId,
		public PaymentStatus $status,
		public Amount $amount,
		public array $rawResponse = []
	) {
	}
}
