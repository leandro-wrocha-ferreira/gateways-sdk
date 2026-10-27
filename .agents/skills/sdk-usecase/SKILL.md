---
name: sdk-usecase
description: Use when creating or modifying application use cases or DTOs for the PHP 8.2 Payment Gateways SDK.
---

# SDK Use Case Layer

## When to Use

Use this skill when creating:
- Use Case classes (e.g., `CreateChargeUseCase.php`, `RefundChargeUseCase.php`, `HandleWebhookUseCase.php`)
- Application DTOs (e.g., `CreateChargeDTO.php`, `ChargeResultDTO.php`)
- Application services that orchestrate payment operations across gateways

## Location

Use cases live in `src/Application/UseCases/<Context>/`
Application DTOs live in `src/Application/DTOs/<Context>/`

Example:
```
src/Application/
├── DTOs/
│   ├── Charge/
│   │   ├── CreateChargeDTO.php
│   │   └── ChargeResultDTO.php
│   └── Webhook/
│       └── WebhookEventDTO.php
└── UseCases/
    ├── Charge/
    │   ├── CreateChargeUseCase.php
    │   └── RefundChargeUseCase.php
    └── Webhook/
        └── HandleWebhookUseCase.php
```

## Namespace

All use case classes use the `GatewaysSdk\Application\UseCases\<Context>` namespace (PSR-4).
All DTO classes use the `GatewaysSdk\Application\DTOs\<Context>` namespace.

---

## Use Case Pattern

```php
<?php

declare(strict_types=1);

namespace GatewaysSdk\Application\UseCases\Charge;

use GatewaysSdk\Application\DTOs\Charge\ChargeResultDTO;
use GatewaysSdk\Application\DTOs\Charge\CreateChargeDTO;
use GatewaysSdk\Domain\Contracts\GatewayInterface;
use GatewaysSdk\Domain\Exceptions\GatewayException;
use GatewaysSdk\Domain\Exceptions\ValidationException;

/**
 * Use case for creating a payment charge.
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
	 * @param CreateChargeDTO $dto Input payment data
	 * @return ChargeResultDTO
	 * @throws ValidationException When payload is invalid
	 * @throws GatewayException When payment processing fails
	 */
	public function execute(CreateChargeDTO $dto): ChargeResultDTO
	{
		$dto->validate();

		return $this->gateway->createCharge($dto);
	}
}
```

---

## Rules

1. **Strict Types**: Every file MUST start with `declare(strict_types=1);`.
2. **Dependency Injection**: Use cases receive dependencies strictly via constructor injection (`GatewayInterface`, `LoggerInterface`, etc.).
3. **Pure Orchestration**: Use cases coordinate validation, domain entities, and gateway calls. They contain no direct HTTP cURL logic or raw SQL.
4. **Single Responsibility**: One use case per business action with a standard `execute()` method.
5. **Throw Semantic Exceptions**: Throw domain exceptions derived from `SdkException` (`ValidationException`, `GatewayException`, etc.). Never throw generic `\RuntimeException`.
6. **Return Typed DTOs / Entities**: Return clean, typed DTOs or domain entities, never untyped associative arrays.
7. **1:1 Unit Test Gate**: Every use case file in `src/Application/UseCases/` MUST have a matching unit test in `tests/Unit/Application/UseCases/` before completion.
8. **Code Style**:
   - Tab indentation.
   - PSR-12 bracket style (`{` on the next line).
   - No single-letter variables (`$index`, `$gateway`, `$dto`).
   - English docblocks on all classes and methods.

---

## Anti-Patterns

❌ **Direct Gateway Coupling**: Instantiating `new AsaasGateway()` directly inside the use case. The gateway adapter must be injected via `GatewayInterface`.
❌ **Returning Raw Vendor Responses**: Returning Asaas or Pagar.me raw JSON arrays directly to the consuming application. Always map to `ChargeResultDTO` or Domain Entity.
❌ **Multiple Actions per Use Case**: Creating a monolithic `PaymentService` with 10 public methods. Use single-action Use Cases (`CreateChargeUseCase`, `GetChargeUseCase`, `RefundChargeUseCase`).
❌ **Missing 1:1 Unit Tests**: Adding a Use Case without a corresponding unit test file.
