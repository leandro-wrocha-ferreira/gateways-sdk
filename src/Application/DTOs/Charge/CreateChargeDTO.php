<?php

declare(strict_types=1);

namespace GatewaysSdk\Application\DTOs\Charge;

use DateTimeImmutable;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Exceptions\ValidationException;
use GatewaysSdk\Domain\ValueObjects\Amount;
use GatewaysSdk\Domain\ValueObjects\CpfCnpj;

/**
 * Data Transfer Object for creating a charge.
 */
readonly class CreateChargeDTO
{
	/**
	 * Constructor.
	 *
	 * @param Amount $amount Monetary amount
	 * @param PaymentMethod $paymentMethod Payment method
	 * @param string $customerName Customer full name
	 * @param string $customerEmail Customer email address
	 * @param CpfCnpj $customerDocument Customer CPF or CNPJ document
	 * @param string|null $customerPhone Customer contact phone
	 * @param string|null $description Transaction description
	 * @param DateTimeImmutable|null $dueDate Expiration / due date
	 * @param array<string, mixed> $metadata Custom metadata
	 */
	public function __construct(
		public Amount $amount,
		public PaymentMethod $paymentMethod,
		public string $customerName,
		public string $customerEmail,
		public CpfCnpj $customerDocument,
		public ?string $customerPhone = null,
		public ?string $description = null,
		public ?DateTimeImmutable $dueDate = null,
		public array $metadata = []
	) {
	}

	/**
	 * Validate required fields.
	 *
	 * @throws ValidationException
	 */
	public function validate(): void
	{
		if (trim($this->customerName) === '') {
			throw new ValidationException('Customer name is required.');
		}

		if (!filter_var($this->customerEmail, FILTER_VALIDATE_EMAIL)) {
			throw new ValidationException("Invalid customer email: {$this->customerEmail}");
		}

		if ($this->amount->cents <= 0) {
			throw new ValidationException('Charge amount must be greater than zero.');
		}
	}
}
