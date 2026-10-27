<?php

declare(strict_types=1);

namespace GatewaysSdk\Tests\Unit\Infrastructure\Gateways\Asaas;

use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\Enums\WebhookEventType;
use GatewaysSdk\Domain\ValueObjects\Amount;
use GatewaysSdk\Domain\ValueObjects\CpfCnpj;
use GatewaysSdk\Infrastructure\Gateways\Asaas\AsaasMapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AsaasMapper.
 */
class AsaasMapperTest extends TestCase
{
	public function testMapsToChargePayload(): void
	{
		$mapper = new AsaasMapper();

		$dto = new CreateChargeDTO(
			amount: new Amount(2500),
			paymentMethod: PaymentMethod::PIX,
			customerName: 'Cliente Teste',
			customerEmail: 'cliente@teste.com',
			customerDocument: new CpfCnpj('529.982.247-25'),
			description: 'Mensalidade do curso'
		);

		$payload = $mapper->toChargePayload($dto, 'cus_000005234123');

		$this->assertSame('cus_000005234123', $payload['customer']);
		$this->assertSame('PIX', $payload['billingType']);
		$this->assertSame(25.0, $payload['value']);
		$this->assertSame('Mensalidade do curso', $payload['description']);
	}

	public function testMapsToChargeResult(): void
	{
		$mapper = new AsaasMapper();

		$response = [
			'id' => 'pay_99887766',
			'status' => 'PENDING',
			'billingType' => 'PIX',
			'value' => 25.0,
			'dueDate' => '2026-11-01',
		];

		$result = $mapper->toChargeResult($response, 'qr_code_base64', 'pix_copy_paste');

		$this->assertSame('pay_99887766', $result->id);
		$this->assertSame('pay_99887766', $result->gatewayId);
		$this->assertSame('asaas', $result->gateway);
		$this->assertSame(PaymentStatus::PENDING, $result->status);
		$this->assertSame(PaymentMethod::PIX, $result->paymentMethod);
		$this->assertSame(2500, $result->amount->cents);
		$this->assertSame('qr_code_base64', $result->pixQrCode);
		$this->assertSame('pix_copy_paste', $result->pixCopyAndPaste);
	}

	public function testMapsWebhookEvent(): void
	{
		$mapper = new AsaasMapper();

		$payload = [
			'id' => 'evt_123',
			'event' => 'PAYMENT_RECEIVED',
			'dateCreated' => '2026-10-27T08:00:00Z',
			'payment' => [
				'id' => 'pay_99887766',
				'status' => 'RECEIVED',
			],
		];

		$event = $mapper->toWebhookEvent($payload);

		$this->assertSame('evt_123', $event->eventId);
		$this->assertSame(WebhookEventType::PAYMENT_PAID, $event->eventType);
		$this->assertSame('asaas', $event->gateway);
		$this->assertSame('pay_99887766', $event->chargeId);
		$this->assertSame(PaymentStatus::PAID, $event->status);
	}
}
