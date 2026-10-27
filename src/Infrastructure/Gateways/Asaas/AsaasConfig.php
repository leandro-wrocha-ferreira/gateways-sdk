<?php

declare(strict_types=1);

namespace GatewaysSdk\Infrastructure\Gateways\Asaas;

/**
 * Configuration settings for Asaas gateway adapter.
 */
readonly class AsaasConfig
{
	public const SANDBOX_URL = 'https://sandbox.asaas.com/api/v3';
	public const PRODUCTION_URL = 'https://api.asaas.com/v3';

	/**
	 * Constructor.
	 *
	 * @param string $apiKey Asaas API access token
	 * @param bool $isSandbox Whether to target sandbox environment
	 * @param string|null $webhookToken Optional token to validate incoming webhooks
	 */
	public function __construct(
		private string $apiKey,
		private bool $isSandbox = true,
		private ?string $webhookToken = null
	) {
	}

	public function getApiKey(): string
	{
		return $this->apiKey;
	}

	public function isSandbox(): bool
	{
		return $this->isSandbox;
	}

	public function getWebhookToken(): ?string
	{
		return $this->webhookToken;
	}

	public function getBaseUrl(): string
	{
		return $this->isSandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
	}
}
