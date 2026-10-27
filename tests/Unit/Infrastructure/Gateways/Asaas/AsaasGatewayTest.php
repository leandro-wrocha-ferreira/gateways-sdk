<?php

declare(strict_types=1);

namespace GatewaysSdk\Tests\Unit\Infrastructure\Gateways\Asaas;

use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Application\DTOs\Charge\RefundChargeDTO;
use GatewaysSdk\Application\DTOs\Webhook\WebhookRequestDTO;
use GatewaysSdk\Domain\Contracts\HttpClientInterface;
use GatewaysSdk\Domain\Contracts\HttpResponseInterface;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\ValueObjects\Amount;
use GatewaysSdk\Domain\ValueObjects\CpfCnpj;
use GatewaysSdk\Infrastructure\Gateways\Asaas\AsaasConfig;
use GatewaysSdk\Infrastructure\Gateways\Asaas\AsaasGateway;
use GatewaysSdk\Infrastructure\Gateways\Asaas\AsaasMapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AsaasGateway adapter.
 */
class AsaasGatewayTest extends TestCase
{
	public function testGetName(): void
	{
		$config = new AsaasConfig('test_api_key', true);
		$httpClient = $this->createMock(HttpClientInterface::class);
		$mapper = new AsaasMapper();

		$gateway = new AsaasGateway($config, $httpClient, $mapper);
		$this->assertSame('asaas', $gateway->getName());
	}

	public function testCreatePixChargeWithCustomerLookup(): void
	{
		$config = new AsaasConfig('test_api_key', true);
		$httpClient = $this->createMock(HttpClientInterface::class);
		$mapper = new AsaasMapper();

		$dto = new CreateChargeDTO(
			amount: new Amount(10000),
			paymentMethod: PaymentMethod::PIX,
			customerName: 'Cliente Teste',
			customerEmail: 'cliente@teste.com',
			customerDocument: new CpfCnpj('529.982.247-25')
		);

		// 1. Search customer response (found)
		$customerSearchResponse = $this->createMock(HttpResponseInterface::class);
		$customerSearchResponse->method('isSuccessful')->willReturn(true);
		$customerSearchResponse->method('json')->willReturn([
			'data' => [
				['id' => 'cus_123456'],
			],
		]);

		// 2. Create payment response
		$paymentResponse = $this->createMock(HttpResponseInterface::class);
		$paymentResponse->method('isSuccessful')->willReturn(true);
		$paymentResponse->method('json')->willReturn([
			'id' => 'pay_78910',
			'status' => 'PENDING',
			'billingType' => 'PIX',
			'value' => 100.0,
			'dueDate' => '2026-10-30',
		]);

		// 3. Pix QR Code response
		$pixResponse = $this->createMock(HttpResponseInterface::class);
		$pixResponse->method('isSuccessful')->willReturn(true);
		$pixResponse->method('json')->willReturn([
			'encodedImage' => 'data:image/png;base64,mock',
			'payload' => 'pix_qr_code_payload',
		]);

		$httpClient->expects($this->exactly(2))
			->method('get')
			->willReturnCallback(function (string $url) use ($customerSearchResponse, $pixResponse) {
				if (str_contains($url, '/customers?cpfCnpj=')) {
					return $customerSearchResponse;
				}
				if (str_contains($url, '/pixQrCode')) {
					return $pixResponse;
				}
				throw new \RuntimeException("Unexpected GET to {$url}");
			});

		$httpClient->expects($this->once())
			->method('post')
			->willReturn($paymentResponse);

		$gateway = new AsaasGateway($config, $httpClient, $mapper);
		$result = $gateway->createCharge($dto);

		$this->assertSame('pay_78910', $result->id);
		$this->assertSame(PaymentStatus::PENDING, $result->status);
		$this->assertSame('pix_qr_code_payload', $result->pixCopyAndPaste);
		$this->assertSame('data:image/png;base64,mock', $result->pixQrCode);
	}

	public function testValidateWebhookToken(): void
	{
		$config = new AsaasConfig('test_api_key', true, 'secret_token_123');
		$httpClient = $this->createMock(HttpClientInterface::class);
		$mapper = new AsaasMapper();

		$gateway = new AsaasGateway($config, $httpClient, $mapper);

		$validRequest = new WebhookRequestDTO([], ['asaas-access-token' => 'secret_token_123']);
		$invalidRequest = new WebhookRequestDTO([], ['asaas-access-token' => 'wrong_token']);

		$this->assertTrue($gateway->validateWebhook($validRequest));
		$this->assertFalse($gateway->validateWebhook($invalidRequest));
	}
}
