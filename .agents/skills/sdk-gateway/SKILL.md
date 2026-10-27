---
name: sdk-gateway
description: Use when creating or modifying gateway adapters (Asaas, Pagarme), gateway mappers, HTTP clients, or webhook handlers in the Infrastructure layer.
---

# SDK Gateway Adapter Layer (Infrastructure)

## When to Use

Use this skill when:
- Implementing or updating payment gateway adapters (e.g., `AsaasGateway.php`, `PagarmeGateway.php`)
- Implementing payload mappers (e.g., `AsaasMapper.php`, `PagarmeMapper.php`)
- Implementing gateway configuration objects (e.g., `AsaasConfig.php`, `PagarmeConfig.php`)
- Writing HTTP client integrations or webhook signature verifiers

## Location

Gateway adapters live in `src/Infrastructure/Gateways/<GatewayName>/`
HTTP client abstractions live in `src/Infrastructure/Http/`

Example:
```
src/Infrastructure/
├── Gateways/
│   ├── Asaas/
│   │   ├── AsaasGateway.php
│   │   ├── AsaasConfig.php
│   │   └── AsaasMapper.php
│   └── Pagarme/
│       ├── PagarmeGateway.php
│       ├── PagarmeConfig.php
│       └── PagarmeMapper.php
├── Http/
│   ├── CurlHttpClient.php
│   ├── HttpRequest.php
│   └── HttpResponse.php
└── GatewayFactory.php
```

## Namespace

Gateway classes use `GatewaysSdk\Infrastructure\Gateways\<GatewayName>` namespace (PSR-4).
HTTP classes use `GatewaysSdk\Infrastructure\Http`.

---

## Gateway Implementation Pattern

All gateway adapters must implement `GatewaysSdk\Domain\Contracts\GatewayInterface`:

```php
<?php

declare(strict_types=1);

namespace GatewaysSdk\Infrastructure\Gateways\Asaas;

use GatewaysSdk\Application\DTOs\Charge\ChargeResultDTO;
use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Domain\Contracts\GatewayInterface;
use GatewaysSdk\Domain\Contracts\HttpClientInterface;
use GatewaysSdk\Domain\Exceptions\GatewayException;

/**
 * Asaas Payment Gateway Adapter.
 */
class AsaasGateway implements GatewayInterface
{
	/**
	 * Constructor.
	 *
	 * @param AsaasConfig $config Gateway credentials and settings
	 * @param HttpClientInterface $httpClient Abstract HTTP client
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
		$payload = $this->mapper->toChargePayload($dto);

		$response = $this->httpClient->post(
			$this->config->getBaseUrl() . '/v3/payments',
			$payload,
			$this->getHeaders()
		);

		if (!$response->isSuccessful()) {
			throw new GatewayException("Asaas payment failed: " . $response->getBody());
		}

		return $this->mapper->toChargeResult($response->json());
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
```

---

## Rules

1. **Implement `GatewayInterface`**: Every gateway adapter must strictly implement all methods declared in `GatewayInterface`.
2. **Isolate Vendor Schemas in Mappers**: All conversions between Domain DTOs and gateway-specific JSON payloads must be contained exclusively within the gateway's `Mapper`.
3. **No Direct `curl_*` in Gateway**: Use the abstracted `HttpClientInterface` to allow mock HTTP testing without hitting real gateway endpoints.
4. **Normalized Error Handling**: Parse vendor error responses (e.g. Asaas error array or Pagarme error object) and throw structured `GatewayException` with readable messages and HTTP status codes.
5. **Webhook Signature Validation**: Each gateway must implement signature/header verification (e.g., Asaas `asaas-access-token` header or Pagarme `X-Hub-Signature`).
6. **Code Style**:
   - Tab indentation.
   - PSR-12 brackets (`{` on the next line).
   - English docblocks.
   - No single-letter variables.

---

## Anti-Patterns

❌ **Hardcoded URLs**: Never hardcode production URLs without sandbox toggling. Use the gateway's `Config` class to provide environment-specific URLs.
❌ **Leaking Vendor Data**: Returning raw gateway JSON arrays to callers instead of normalized `ChargeResultDTO`.
❌ **Skipping Webhook Verification**: Processing incoming webhooks without verifying token or signature headers.
