<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\ValueObjects;

use GatewaysSdk\Domain\Exceptions\ValidationException;

/**
 * Value Object representing a monetary amount stored in cents.
 */
readonly class Amount
{
	/**
	 * Constructor.
	 *
	 * @param int $cents Amount in integer cents
	 * @param string $currency ISO-4217 currency code
	 * @throws ValidationException If amount is negative
	 */
	public function __construct(
		public int $cents,
		public string $currency = 'BRL'
	) {
		if ($cents < 0) {
			throw new ValidationException("Amount cannot be negative: {$cents}");
		}
	}

	/**
	 * Create Amount from float decimal (e.g. 19.90 -> 1990 cents).
	 *
	 * @param float $decimal Float representation
	 * @param string $currency Currency code
	 * @return self
	 */
	public static function fromFloat(float $decimal, string $currency = 'BRL'): self
	{
		$cents = (int) round($decimal * 100);
		return new self($cents, $currency);
	}

	/**
	 * Create Amount from integer cents.
	 *
	 * @param int $cents Integer cents
	 * @param string $currency Currency code
	 * @return self
	 */
	public static function fromCents(int $cents, string $currency = 'BRL'): self
	{
		return new self($cents, $currency);
	}

	public function toFloat(): float
	{
		return $this->cents / 100.0;
	}

	public function formatted(): string
	{
		return number_format($this->toFloat(), 2, ',', '.');
	}

	public function equals(Amount $other): bool
	{
		return $this->cents === $other->cents && $this->currency === $other->currency;
	}

	public function __toString(): string
	{
		return (string) $this->cents;
	}
}
