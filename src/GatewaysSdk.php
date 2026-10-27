<?php

declare(strict_types=1);

namespace GatewaysSdk;

use GatewaysSdk\Application\DTOs\Charge\ChargeResultDTO;
use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Application\DTOs\Charge\RefundChargeDTO;
use GatewaysSdk\Application\DTOs\Charge\RefundResultDTO;
use GatewaysSdk\Application\DTOs\Webhook\WebhookEventDTO;
use GatewaysSdk\Application\DTOs\Webhook\WebhookRequestDTO;
use GatewaysSdk\Application\UseCases\Charge\CreateChargeUseCase;
use GatewaysSdk\Domain\Contracts\GatewayInterface;
use GatewaysSdk\Domain\Contracts\HttpClientInterface;
use GatewaysSdk\Domain\Exceptions\ValidationException;
use GatewaysSdk\Infrastructure\Gateways\Asaas\AsaasConfig;
use GatewaysSdk\Infrastructure\Gateways\Asaas\AsaasGateway;
use GatewaysSdk\Infrastructure\Gateways\Asaas\AsaasMapper;
use GatewaysSdk\Infrastructure\Http\CurlHttpClient;

/**
 * Main SDK Entrypoint / Facade for client applications.
 */
class GatewaysSdk
{
	/** @var array<string, GatewayInterface> */
	private array $gateways = [];

	private string $defaultGateway;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Configuration array
	 * @param HttpClientInterface|null $httpClient Custom HTTP client
	 * @throws ValidationException
	 */
	public function __construct(
		array $config,
		?HttpClientInterface $httpClient = null
	) {
		$client = $httpClient ?? new CurlHttpClient();

		$this->defaultGateway = (string) ($config['default'] ?? 'asaas');

		$gatewaysConfig = is_array($config['gateways'] ?? null) ? $config['gateways'] : [];

		if (isset($gatewaysConfig['asaas']) && is_array($gatewaysConfig['asaas'])) {
			$asaasOptions = $gatewaysConfig['asaas'];
			$asaasConfig = new AsaasConfig(
				apiKey: (string) ($asaasOptions['api_key'] ?? ''),
				isSandbox: (bool) ($asaasOptions['sandbox'] ?? true),
				webhookToken: isset($asaasOptions['webhook_token']) ? (string) $asaasOptions['webhook_token'] : null
			);
			$this->gateways['asaas'] = new AsaasGateway($asaasConfig, $client, new AsaasMapper());
		}
	}

	/**
	 * Get a specific gateway adapter or the default one.
	 *
	 * @param string|null $name Gateway name ('asaas', 'pagarme')
	 * @return GatewayInterface
	 * @throws ValidationException
	 */
	public function gateway(?string $name = null): GatewayInterface
	{
		$gatewayName = $name ?? $this->defaultGateway;

		if (!isset($this->gateways[$gatewayName])) {
			throw new ValidationException("Gateway '{$gatewayName}' is not configured or supported.");
		}

		return $this->gateways[$gatewayName];
	}

	/**
	 * Create a charge using the active gateway.
	 *
	 * @param CreateChargeDTO $dto
	 * @param string|null $gateway Optional gateway override
	 * @return ChargeResultDTO
	 */
	public function createCharge(CreateChargeDTO $dto, ?string $gateway = null): ChargeResultDTO
	{
		$useCase = new CreateChargeUseCase($this->gateway($gateway));

		return $useCase->execute($dto);
	}

	/**
	 * Retrieve a charge by ID.
	 *
	 * @param string $chargeId
	 * @param string|null $gateway
	 * @return ChargeResultDTO
	 */
	public function getCharge(string $chargeId, ?string $gateway = null): ChargeResultDTO
	{
		return $this->gateway($gateway)->getCharge($chargeId);
	}

	/**
	 * Cancel a charge by ID.
	 *
	 * @param string $chargeId
	 * @param string|null $gateway
	 * @return ChargeResultDTO
	 */
	public function cancelCharge(string $chargeId, ?string $gateway = null): ChargeResultDTO
	{
		return $this->gateway($gateway)->cancelCharge($chargeId);
	}

	/**
	 * Refund a charge.
	 *
	 * @param RefundChargeDTO $dto
	 * @param string|null $gateway
	 * @return RefundResultDTO
	 */
	public function refundCharge(RefundChargeDTO $dto, ?string $gateway = null): RefundResultDTO
	{
		return $this->gateway($gateway)->refundCharge($dto);
	}

	/**
	 * Validate and parse incoming webhook request.
	 *
	 * @param WebhookRequestDTO $request
	 * @param string|null $gateway
	 * @return WebhookEventDTO
	 */
	public function handleWebhook(WebhookRequestDTO $request, ?string $gateway = null): WebhookEventDTO
	{
		$adapter = $this->gateway($gateway);

		if (!$adapter->validateWebhook($request)) {
			throw new ValidationException('Webhook signature or token validation failed.');
		}

		return $adapter->parseWebhook($request);
	}
}
