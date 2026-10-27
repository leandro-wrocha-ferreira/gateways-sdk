<?php

declare(strict_types=1);

namespace GatewaysSdk\Application\DTOs\Charge;

use DateTimeImmutable;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\ValueObjects\Amount;

/**
 * Agnostic result of a charge operation.
 */
readonly class ChargeResultDTO
{
	/**
	 * Constructor.
	 *
	 * @param string $id Unique SDK identifier or gateway reference
	 * @param string $gatewayId Identifier assigned by the payment gateway
	 * @param string $gateway Name of the payment gateway (asaas, pagarme)
	 * @param PaymentStatus $status Normalized status
	 * @param PaymentMethod $paymentMethod Payment method used
	 * @param Amount $amount Monetary amount
	 * @param string|null $pixQrCode Base64 or image URL of PIX QR Code
	 * @param string|null $pixCopyAndPaste PIX payload copy-and-paste string
	 * @param string|null $boletoBarcode Boleto line digits
	 * @param string|null $boletoUrl URL to view or download boleto
	 * @param DateTimeImmutable|null $dueDate Expiration / due date
	 * @param array<string, mixed> $rawResponse Raw response payload for audit/debugging
	 */
	public function __construct(
		public string $id,
		public string $gatewayId,
		public string $gateway,
		public PaymentStatus $status,
		public PaymentMethod $paymentMethod,
		public Amount $amount,
		public ?string $pixQrCode = null,
		public ?string $pixCopyAndPaste = null,
		public ?string $boletoBarcode = null,
		public ?string $boletoUrl = null,
		public ?DateTimeImmutable $dueDate = null,
		public array $rawResponse = []
	) {
	}
}
