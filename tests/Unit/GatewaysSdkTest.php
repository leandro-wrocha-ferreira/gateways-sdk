<?php

declare(strict_types=1);

namespace GatewaysSdk\Tests\Unit;

use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Domain\Contracts\HttpClientInterface;
use GatewaysSdk\Domain\Contracts\HttpResponseInterface;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\Exceptions\ValidationException;
use GatewaysSdk\Domain\ValueObjects\Amount;
use GatewaysSdk\Domain\ValueObjects\CpfCnpj;
use GatewaysSdk\GatewaysSdk;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GatewaysSdk facade.
 */
class GatewaysSdkTest extends TestCase
{
	public function testInitializeSdkWithAsaas(): void
	{
		$httpClient = $this->createMock(HttpClientInterface::class);

		$sdk = new GatewaysSdk([
			'default' => 'asaas',
			'gateways' => [
				'asaas' => [
					'api_key' => 'test_key',
					'sandbox' => true,
				],
			],
		], $httpClient);

		$gateway = $sdk->gateway('asaas');
		$this->assertSame('asaas', $gateway->getName());
	}

	public function testThrowsExceptionWhenGatewayNotConfigured(): void
	{
		$httpClient = $this->createMock(HttpClientInterface::class);

		$sdk = new GatewaysSdk([
			'default' => 'asaas',
			'gateways' => [],
		], $httpClient);

		$this->expectException(ValidationException::class);
		$this->expectExceptionMessage("Gateway 'asaas' is not configured");

		$sdk->gateway();
	}
}
