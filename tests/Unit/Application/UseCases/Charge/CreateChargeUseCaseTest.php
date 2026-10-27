<?php

declare(strict_types=1);

namespace GatewaysSdk\Tests\Unit\Application\UseCases\Charge;

use GatewaysSdk\Application\DTOs\Charge\ChargeResultDTO;
use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Application\UseCases\Charge\CreateChargeUseCase;
use GatewaysSdk\Domain\Contracts\GatewayInterface;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\Exceptions\ValidationException;
use GatewaysSdk\Domain\ValueObjects\Amount;
use GatewaysSdk\Domain\ValueObjects\CpfCnpj;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CreateChargeUseCase.
 */
class CreateChargeUseCaseTest extends TestCase
{
	public function testSuccessfulChargeExecution(): void
	{
		$gatewayMock = $this->createMock(GatewayInterface::class);

		$dto = new CreateChargeDTO(
			amount: new Amount(5000),
			paymentMethod: PaymentMethod::PIX,
			customerName: 'Leandro Thinkr',
			customerEmail: 'leandro@example.com',
			customerDocument: new CpfCnpj('529.982.247-25')
		);

		$expectedResult = new ChargeResultDTO(
			id: 'pay_123456',
			gatewayId: 'pay_123456',
			gateway: 'asaas',
			status: PaymentStatus::PENDING,
			paymentMethod: PaymentMethod::PIX,
			amount: new Amount(5000)
		);

		$gatewayMock->expects($this->once())
			->method('createCharge')
			->with($dto)
			->willReturn($expectedResult);

		$useCase = new CreateChargeUseCase($gatewayMock);
		$result = $useCase->execute($dto);

		$this->assertSame('pay_123456', $result->id);
		$this->assertSame(PaymentStatus::PENDING, $result->status);
		$this->assertSame(5000, $result->amount->cents);
	}

	public function testThrowsValidationExceptionWhenCustomerNameEmpty(): void
	{
		$gatewayMock = $this->createMock(GatewayInterface::class);

		$dto = new CreateChargeDTO(
			amount: new Amount(5000),
			paymentMethod: PaymentMethod::PIX,
			customerName: '',
			customerEmail: 'leandro@example.com',
			customerDocument: new CpfCnpj('529.982.247-25')
		);

		$this->expectException(ValidationException::class);
		$this->expectExceptionMessage('Customer name is required');

		$useCase = new CreateChargeUseCase($gatewayMock);
		$useCase->execute($dto);
	}
}
