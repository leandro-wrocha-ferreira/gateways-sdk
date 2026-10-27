<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Contracts;

use GatewaysSdk\Application\DTOs\Charge\ChargeResultDTO;
use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Application\DTOs\Charge\RefundChargeDTO;
use GatewaysSdk\Application\DTOs\Charge\RefundResultDTO;
use GatewaysSdk\Application\DTOs\Webhook\WebhookEventDTO;
use GatewaysSdk\Application\DTOs\Webhook\WebhookRequestDTO;
use GatewaysSdk\Domain\Exceptions\GatewayException;
use GatewaysSdk\Domain\Exceptions\ValidationException;

/**
 * Common contract that every payment gateway adapter must implement.
 */
interface GatewayInterface
{
	/**
	 * Get the gateway identifier name (e.g. 'asaas', 'pagarme').
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Create a new payment charge.
	 *
	 * @param CreateChargeDTO $dto Charge input parameters
	 * @return ChargeResultDTO
	 * @throws ValidationException
	 * @throws GatewayException
	 */
	public function createCharge(CreateChargeDTO $dto): ChargeResultDTO;

	/**
	 * Retrieve charge details by gateway ID.
	 *
	 * @param string $chargeId Gateway charge identifier
	 * @return ChargeResultDTO
	 * @throws GatewayException
	 */
	public function getCharge(string $chargeId): ChargeResultDTO;

	/**
	 * Cancel a pending charge.
	 *
	 * @param string $chargeId Gateway charge identifier
	 * @return ChargeResultDTO
	 * @throws GatewayException
	 */
	public function cancelCharge(string $chargeId): ChargeResultDTO;

	/**
	 * Refund a charge partially or totally.
	 *
	 * @param RefundChargeDTO $dto Refund parameters
	 * @return RefundResultDTO
	 * @throws GatewayException
	 */
	public function refundCharge(RefundChargeDTO $dto): RefundResultDTO;

	/**
	 * Verify webhook signature and token validity.
	 *
	 * @param WebhookRequestDTO $request Incoming webhook request
	 * @return bool
	 */
	public function validateWebhook(WebhookRequestDTO $request): bool;

	/**
	 * Parse raw webhook payload into normalized event.
	 *
	 * @param WebhookRequestDTO $request Incoming webhook request
	 * @return WebhookEventDTO
	 * @throws ValidationException
	 */
	public function parseWebhook(WebhookRequestDTO $request): WebhookEventDTO;
}
