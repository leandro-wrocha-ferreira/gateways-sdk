<?php

declare(strict_types=1);

namespace GatewaysSdk\Application\DTOs\Webhook;

use DateTimeImmutable;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\Enums\WebhookEventType;

/**
 * Normalized webhook event parsed across gateways.
 */
readonly class WebhookEventDTO
{
	/**
	 * Constructor.
	 *
	 * @param string $eventId Event unique identifier
	 * @param WebhookEventType $eventType Normalized event type
	 * @param string $gateway Gateway origin ('asaas', 'pagarme')
	 * @param string $chargeId Related charge identifier
	 * @param PaymentStatus $status Normalized status
	 * @param array<string, mixed> $rawPayload Raw original payload
	 * @param DateTimeImmutable $occurredAt Timestamp of event occurrence
	 */
	public function __construct(
		public string $eventId,
		public WebhookEventType $eventType,
		public string $gateway,
		public string $chargeId,
		public PaymentStatus $status,
		public array $rawPayload,
		public DateTimeImmutable $occurredAt
	) {
	}
}
