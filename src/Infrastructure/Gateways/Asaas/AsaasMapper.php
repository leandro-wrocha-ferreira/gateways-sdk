<?php

declare(strict_types=1);

namespace GatewaysSdk\Infrastructure\Gateways\Asaas;

use DateTimeImmutable;
use GatewaysSdk\Application\DTOs\Charge\ChargeResultDTO;
use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Application\DTOs\Webhook\WebhookEventDTO;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\Enums\WebhookEventType;
use GatewaysSdk\Domain\ValueObjects\Amount;

/**
 * Mapper for translating between SDK DTOs and Asaas API payloads.
 */
class AsaasMapper
{
	/**
	 * Convert CreateChargeDTO into Asaas payment creation payload.
	 *
	 * @param CreateChargeDTO $dto
	 * @param string $customerId Asaas customer ID
	 * @return array<string, mixed>
	 */
	public function toChargePayload(CreateChargeDTO $dto, string $customerId): array
	{
		$billingType = match ($dto->paymentMethod) {
			PaymentMethod::PIX => 'PIX',
			PaymentMethod::BOLETO => 'BOLETO',
			PaymentMethod::CREDIT_CARD => 'CREDIT_CARD',
		};

		$payload = [
			'customer' => $customerId,
			'billingType' => $billingType,
			'value' => $dto->amount->toFloat(),
			'dueDate' => $dto->dueDate?->format('Y-m-d') ?? (new DateTimeImmutable('+3 days'))->format('Y-m-d'),
		];

		if ($dto->description !== null) {
			$payload['description'] = $dto->description;
		}

		return $payload;
	}

	/**
	 * Convert Asaas API response into normalized ChargeResultDTO.
	 *
	 * @param array<string, mixed> $response
	 * @param string|null $pixQrCode
	 * @param string|null $pixCopyAndPaste
	 * @return ChargeResultDTO
	 */
	public function toChargeResult(
		array $response,
		?string $pixQrCode = null,
		?string $pixCopyAndPaste = null
	): ChargeResultDTO {
		$gatewayId = (string) ($response['id'] ?? '');
		$status = $this->mapPaymentStatus((string) ($response['status'] ?? ''));
		$paymentMethod = $this->mapPaymentMethod((string) ($response['billingType'] ?? ''));
		$amount = Amount::fromFloat((float) ($response['value'] ?? 0.0));

		$dueDate = null;
		if (isset($response['dueDate']) && is_string($response['dueDate'])) {
			$dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $response['dueDate']) ?: null;
		}

		return new ChargeResultDTO(
			id: $gatewayId,
			gatewayId: $gatewayId,
			gateway: 'asaas',
			status: $status,
			paymentMethod: $paymentMethod,
			amount: $amount,
			pixQrCode: $pixQrCode,
			pixCopyAndPaste: $pixCopyAndPaste,
			boletoBarcode: $response['identificationField'] ?? null,
			boletoUrl: $response['bankSlipUrl'] ?? null,
			dueDate: $dueDate,
			rawResponse: $response
		);
	}

	/**
	 * Map Asaas webhook payload into normalized WebhookEventDTO.
	 *
	 * @param array<string, mixed> $payload
	 * @return WebhookEventDTO
	 */
	public function toWebhookEvent(array $payload): WebhookEventDTO
	{
		$eventRaw = (string) ($payload['event'] ?? '');
		$paymentData = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
		$chargeId = (string) ($paymentData['id'] ?? '');

		$eventType = match ($eventRaw) {
			'PAYMENT_CREATED' => WebhookEventType::PAYMENT_CREATED,
			'PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED' => WebhookEventType::PAYMENT_PAID,
			'PAYMENT_OVERDUE' => WebhookEventType::PAYMENT_OVERDUE,
			'PAYMENT_REFUNDED' => WebhookEventType::PAYMENT_REFUNDED,
			'PAYMENT_DELETED' => WebhookEventType::PAYMENT_CANCELLED,
			default => WebhookEventType::UNKNOWN,
		};

		$status = $this->mapPaymentStatus((string) ($paymentData['status'] ?? ''));

		return new WebhookEventDTO(
			eventId: (string) ($payload['id'] ?? uniqid('evt_', true)),
			eventType: $eventType,
			gateway: 'asaas',
			chargeId: $chargeId,
			status: $status,
			rawPayload: $payload,
			occurredAt: new DateTimeImmutable((string) ($payload['dateCreated'] ?? 'now'))
		);
	}

	public function mapPaymentStatus(string $gatewayStatus): PaymentStatus
	{
		return match (strtoupper($gatewayStatus)) {
			'CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH' => PaymentStatus::PAID,
			'PENDING' => PaymentStatus::PENDING,
			'OVERDUE' => PaymentStatus::OVERDUE,
			'REFUNDED' => PaymentStatus::REFUNDED,
			'PARTIALLY_REFUNDED' => PaymentStatus::PARTIALLY_REFUNDED,
			'CANCELLED', 'DELETED' => PaymentStatus::CANCELLED,
			default => PaymentStatus::FAILED,
		};
	}

	public function mapPaymentMethod(string $billingType): PaymentMethod
	{
		return match (strtoupper($billingType)) {
			'PIX' => PaymentMethod::PIX,
			'BOLETO' => PaymentMethod::BOLETO,
			'CREDIT_CARD' => PaymentMethod::CREDIT_CARD,
			default => PaymentMethod::PIX,
		};
	}
}
