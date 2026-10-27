<?php

declare(strict_types=1);

namespace GatewaysSdk\Application\UseCases\Charge;

use GatewaysSdk\Application\DTOs\Charge\ChargeResultDTO;
use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Domain\Contracts\GatewayInterface;
use GatewaysSdk\Domain\Exceptions\GatewayException;
use GatewaysSdk\Domain\Exceptions\ValidationException;

/**
 * Use case to orchestrate charge creation through a payment gateway.
 */
class CreateChargeUseCase
{
	/**
	 * Constructor.
	 *
	 * @param GatewayInterface $gateway Selected payment gateway adapter
	 */
	public function __construct(
		private readonly GatewayInterface $gateway
	) {
	}

	/**
	 * Execute charge creation.
	 *
	 * @param CreateChargeDTO $dto Charge parameters
	 * @return ChargeResultDTO
	 * @throws ValidationException
	 * @throws GatewayException
	 */
	public function execute(CreateChargeDTO $dto): ChargeResultDTO
	{
		$dto->validate();

		return $this->gateway->createCharge($dto);
	}
}
