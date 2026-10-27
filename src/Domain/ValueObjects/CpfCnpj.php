<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\ValueObjects;

use GatewaysSdk\Domain\Exceptions\ValidationException;

/**
 * Value Object representing a Brazilian CPF or CNPJ document.
 */
readonly class CpfCnpj
{
	public string $value;

	/**
	 * Constructor.
	 *
	 * @param string $document Raw document string
	 * @throws ValidationException If document is invalid
	 */
	public function __construct(string $document)
	{
		$cleaned = preg_replace('/\D/', '', $document) ?? '';

		if (!$this->isValid($cleaned)) {
			throw new ValidationException("Invalid CPF or CNPJ document: {$document}");
		}

		$this->value = $cleaned;
	}

	public function isCpf(): bool
	{
		return strlen($this->value) === 11;
	}

	public function isCnpj(): bool
	{
		return strlen($this->value) === 14;
	}

	public function formatted(): string
	{
		if ($this->isCpf()) {
			return preg_replace(
				'/(\d{3})(\d{3})(\d{3})(\d{2})/',
				'$1.$2.$3-$4',
				$this->value
			) ?? $this->value;
		}

		return preg_replace(
			'/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
			'$1.$2.$3/$4-$5',
			$this->value
		) ?? $this->value;
	}

	public function equals(CpfCnpj $other): bool
	{
		return $this->value === $other->value;
	}

	public function __toString(): string
	{
		return $this->value;
	}

	private function isValid(string $digits): bool
	{
		$length = strlen($digits);

		if ($length === 11) {
			return $this->isValidCpf($digits);
		}

		if ($length === 14) {
			return $this->isValidCnpj($digits);
		}

		return false;
	}

	private function isValidCpf(string $digits): bool
	{
		if (preg_match('/^(\d)\1*$/', $digits)) {
			return false;
		}

		$firstSum = 0;
		for ($index = 0; $index < 9; $index++) {
			$firstSum += ((int) $digits[$index]) * (10 - $index);
		}
		$firstRemainder = $firstSum % 11;
		$firstDigit = $firstRemainder < 2 ? 0 : 11 - $firstRemainder;

		if ((int) $digits[9] !== $firstDigit) {
			return false;
		}

		$secondSum = 0;
		for ($index = 0; $index < 10; $index++) {
			$secondSum += ((int) $digits[$index]) * (11 - $index);
		}
		$secondRemainder = $secondSum % 11;
		$secondDigit = $secondRemainder < 2 ? 0 : 11 - $secondRemainder;

		return (int) $digits[10] === $secondDigit;
	}

	private function isValidCnpj(string $digits): bool
	{
		if (preg_match('/^(\d)\1*$/', $digits)) {
			return false;
		}

		$multipliersFirst = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
		$firstSum = 0;
		foreach ($multipliersFirst as $index => $multiplier) {
			$firstSum += ((int) $digits[$index]) * $multiplier;
		}
		$firstRemainder = $firstSum % 11;
		$firstDigit = $firstRemainder < 2 ? 0 : 11 - $firstRemainder;

		if ((int) $digits[12] !== $firstDigit) {
			return false;
		}

		$multipliersSecond = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
		$secondSum = 0;
		foreach ($multipliersSecond as $index => $multiplier) {
			$secondSum += ((int) $digits[$index]) * $multiplier;
		}
		$secondRemainder = $secondSum % 11;
		$secondDigit = $secondRemainder < 2 ? 0 : 11 - $secondRemainder;

		return (int) $digits[13] === $secondDigit;
	}
}
