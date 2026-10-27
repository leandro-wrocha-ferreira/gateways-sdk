<?php

declare(strict_types=1);

namespace GatewaysSdk\Infrastructure\Gateways\Asaas;

use GatewaysSdk\Application\DTOs\Charge\ChargeResultDTO;
use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Application\DTOs\Charge\RefundChargeDTO;
use GatewaysSdk\Application\DTOs\Charge\RefundResultDTO;
use GatewaysSdk\Application\DTOs\Webhook\WebhookEventDTO;
use GatewaysSdk\Application\DTOs\Webhook\WebhookRequestDTO;
use GatewaysSdk\Domain\Contracts\GatewayInterface;
use GatewaysSdk\Domain\Contracts\HttpClientInterface;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\Exceptions\GatewayException;
use GatewaysSdk\Domain\Exceptions\ValidationException;
use GatewaysSdk\Domain\ValueObjects\Amount;

/**
 * Asaas Payment Gateway Adapter.
 */
class AsaasGateway implements GatewayInterface
{
	/**
	 * Constructor.
	 *
	 * @param AsaasConfig $config Gateway configuration
	 * @param HttpClientInterface $httpClient HTTP client
	 * @param AsaasMapper $mapper Payload translator
	 */
	public function __construct(
		private readonly AsaasConfig $config,
		private readonly HttpClientInterface $httpClient,
		private readonly AsaasMapper $mapper
	) {
	}

	public function getName(): string
	{
		return 'asaas';
	}

	public function createCharge(CreateChargeDTO $dto): ChargeResultDTO
	{
		$customerId = $this->findOrCreateCustomer($dto);

		$payload = $this->mapper->toChargePayload($dto, $customerId);

		$response = $this->httpClient->post(
			$this->config->getBaseUrl() . '/payments',
			$payload,
			$this->getHeaders()
		);

		if (!$response->isSuccessful()) {
			throw new GatewayException(
				"Asaas charge creation failed: {$response->getBody()}",
				$response->getStatusCode(),
				$response->json()
			);
		}

		$responseData = $response->json();
		$paymentId = (string) ($responseData['id'] ?? '');

		$pixQrCode = null;
		$pixCopyAndPaste = null;

		if ($dto->paymentMethod === PaymentMethod::PIX && $paymentId !== '') {
			$pixData = $this->fetchPixQrCode($paymentId);
			$pixQrCode = $pixData['encodedImage'] ?? null;
			$pixCopyAndPaste = $pixData['payload'] ?? null;
		}

		return $this->mapper->toChargeResult($responseData, $pixQrCode, $pixCopyAndPaste);
	}

	public function getCharge(string $chargeId): ChargeResultDTO
	{
		$response = $this->httpClient->get(
			$this->config->getBaseUrl() . "/payments/{$chargeId}",
			$this->getHeaders()
		);

		if (!$response->isSuccessful()) {
			throw new GatewayException(
				"Asaas failed to retrieve charge: {$response->getBody()}",
				$response->getStatusCode(),
				$response->json()
			);
		}

		return $this->mapper->toChargeResult($response->json());
	}

	public function cancelCharge(string $chargeId): ChargeResultDTO
	{
		$response = $this->httpClient->delete(
			$this->config->getBaseUrl() . "/payments/{$chargeId}",
			$this->getHeaders()
		);

		if (!$response->isSuccessful()) {
			throw new GatewayException(
				"Asaas failed to cancel charge: {$response->getBody()}",
				$response->getStatusCode(),
				$response->json()
			);
		}

		return $this->mapper->toChargeResult($response->json());
	}

	public function refundCharge(RefundChargeDTO $dto): RefundResultDTO
	{
		$payload = [];
		if ($dto->amount !== null) {
			$payload['value'] = $dto->amount->toFloat();
		}
		if ($dto->reason !== null) {
			$payload['description'] = $dto->reason;
		}

		$response = $this->httpClient->post(
			$this->config->getBaseUrl() . "/payments/{$dto->chargeId}/refund",
			$payload,
			$this->getHeaders()
		);

		if (!$response->isSuccessful()) {
			throw new GatewayException(
				"Asaas failed to refund charge: {$response->getBody()}",
				$response->getStatusCode(),
				$response->json()
			);
		}

		$responseData = $response->json();
		$status = $this->mapper->mapPaymentStatus((string) ($responseData['status'] ?? 'REFUNDED'));
		$refundedAmount = $dto->amount ?? Amount::fromFloat((float) ($responseData['value'] ?? 0.0));

		return new RefundResultDTO(
			refundId: (string) ($responseData['id'] ?? uniqid('ref_', true)),
			chargeId: $dto->chargeId,
			status: $status,
			amount: $refundedAmount,
			rawResponse: $responseData
		);
	}

	public function validateWebhook(WebhookRequestDTO $request): bool
	{
		$configuredToken = $this->config->getWebhookToken();
		if ($configuredToken === null || $configuredToken === '') {
			return true;
		}

		$incomingToken = $request->headers['asaas-access-token']
			?? $request->headers['Asaas-Access-Token']
			?? null;

		return hash_equals($configuredToken, (string) $incomingToken);
	}

	public function parseWebhook(WebhookRequestDTO $request): WebhookEventDTO
	{
		$payload = is_array($request->payload)
			? $request->payload
			: (json_decode($request->payload, true) ?: []);

		if (!is_array($payload) || empty($payload)) {
			throw new ValidationException('Invalid webhook JSON payload.');
		}

		return $this->mapper->toWebhookEvent($payload);
	}

	/**
	 * Find customer by CPF/CNPJ or create customer if not found.
	 *
	 * @param CreateChargeDTO $dto
	 * @return string Asaas customer ID
	 * @throws GatewayException
	 */
	private function findOrCreateCustomer(CreateChargeDTO $dto): string
	{
		$searchUrl = $this->config->getBaseUrl() . '/customers?cpfCnpj=' . urlencode($dto->customerDocument->value);
		$searchResponse = $this->httpClient->get($searchUrl, $this->getHeaders());

		if ($searchResponse->isSuccessful()) {
			$searchData = $searchResponse->json();
			$dataList = $searchData['data'] ?? [];
			if (!empty($dataList) && isset($dataList[0]['id'])) {
				return (string) $dataList[0]['id'];
			}
		}

		$createPayload = [
			'name' => $dto->customerName,
			'email' => $dto->customerEmail,
			'cpfCnpj' => $dto->customerDocument->value,
		];

		if ($dto->customerPhone !== null) {
			$createPayload['phone'] = $dto->customerPhone;
		}

		$createResponse = $this->httpClient->post(
			$this->config->getBaseUrl() . '/customers',
			$createPayload,
			$this->getHeaders()
		);

		if (!$createResponse->isSuccessful()) {
			throw new GatewayException(
				"Asaas customer creation failed: {$createResponse->getBody()}",
				$createResponse->getStatusCode(),
				$createResponse->json()
			);
		}

		$customerData = $createResponse->json();
		return (string) ($customerData['id'] ?? '');
	}

	/**
	 * Fetch PIX QR code and copy-and-paste payload from Asaas.
	 *
	 * @param string $paymentId
	 * @return array<string, mixed>
	 */
	private function fetchPixQrCode(string $paymentId): array
	{
		$pixUrl = $this->config->getBaseUrl() . "/payments/{$paymentId}/pixQrCode";
		$response = $this->httpClient->get($pixUrl, $this->getHeaders());

		if ($response->isSuccessful()) {
			return $response->json();
		}

		return [];
	}

	/**
	 * @return array<string, string>
	 */
	private function getHeaders(): array
	{
		return [
			'access_token' => $this->config->getApiKey(),
			'Content-Type' => 'application/json',
			'User-Agent' => 'GatewaysSdk/1.0',
		];
	}
}
