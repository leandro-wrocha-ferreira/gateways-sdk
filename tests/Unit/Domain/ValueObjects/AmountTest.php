<?php

declare(strict_types=1);

namespace GatewaysSdk\Tests\Unit\Domain\ValueObjects;

use GatewaysSdk\Domain\Exceptions\ValidationException;
use GatewaysSdk\Domain\ValueObjects\Amount;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Amount Value Object.
 */
class AmountTest extends TestCase
{
	public function testCanInstantiateFromCents(): void
	{
		$amount = new Amount(1990);

		$this->assertSame(1990, $amount->cents);
		$this->assertSame(19.90, $amount->toFloat());
		$this->assertSame('19,90', $amount->formatted());
		$this->assertSame('1990', (string) $amount);
	}

	public function testCanInstantiateFromFloat(): void
	{
		$amount = Amount::fromFloat(150.75);

		$this->assertSame(15075, $amount->cents);
		$this->assertSame(150.75, $amount->toFloat());
		$this->assertSame('150,75', $amount->formatted());
	}

	public function testThrowsExceptionWhenNegative(): void
	{
		$this->expectException(ValidationException::class);
		$this->expectExceptionMessage('Amount cannot be negative');

		new Amount(-100);
	}

	public function testEqualsComparison(): void
	{
		$firstAmount = new Amount(5000, 'BRL');
		$secondAmount = new Amount(5000, 'BRL');
		$thirdAmount = new Amount(5000, 'USD');

		$this->assertTrue($firstAmount->equals($secondAmount));
		$this->assertFalse($firstAmount->equals($thirdAmount));
	}
}
