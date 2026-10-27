<?php

declare(strict_types=1);

namespace GatewaysSdk\Application\DTOs\Webhook;

/**
 * Incoming raw webhook request payload and headers.
 */
readonly class WebhookRequestDTO
{
	/**
	 * Constructor.
	 *
	 * @param array<string, mixed>|string $payload Raw request body payload
	 * @param array<string, string> $headers HTTP request headers
	 */
	public function __construct(
		public array|string $payload,
		public array $headers = []
	) {
	}
}
