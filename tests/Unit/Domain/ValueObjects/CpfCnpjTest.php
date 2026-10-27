<?php

declare(strict_types=1);

namespace GatewaysSdk\Tests\Unit\Domain\ValueObjects;

use GatewaysSdk\Domain\Exceptions\ValidationException;
use GatewaysSdk\Domain\ValueObjects\CpfCnpj;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CpfCnpj Value Object.
 */
class CpfCnpjTest extends TestCase
{
	public function testValidCpf(): void
	{
		// Standard valid test CPF (known valid check digits)
		$validCpf = '01234567890';
		// Let's use a mathematically valid algorithm test CPF: 52998224725
		$cpf = new CpfCnpj('529.982.247-25');

		$this->assertTrue($cpf->isCpf());
		$this->assertFalse($cpf->isCnpj());
		$this->assertSame('52998224725', $cpf->value);
		$this->assertSame('529.982.247-25', $cpf->formatted());
	}

	public function testValidCnpj(): void
	{
		// Valid algorithm test CNPJ: 11.222.333/0001-81
		$cnpj = new CpfCnpj('11.222.333/0001-81');

		$this->assertFalse($cnpj->isCpf());
		$this->assertTrue($cnpj->isCnpj());
		$this->assertSame('11222333000181', $cnpj->value);
		$this->assertSame('11.222.333/0001-81', $cnpj->formatted());
	}

	public function testThrowsExceptionOnInvalidDocument(): void
	{
		$this->expectException(ValidationException::class);
		$this->expectExceptionMessage('Invalid CPF or CNPJ document');

		new CpfCnpj('123.456.789-00');
	}
}
