---
name: sdk-domain
description: Use when creating or modifying domain entities, value objects, enums, domain exceptions, or gateway interfaces for the modern PHP 8.2 Payment Gateways SDK.
---

# SDK Domain Layer

## When to Use

Use this skill when creating or modifying:
- Domain Entities (e.g., `Charge`, `Customer`, `Refund`, `Subscription`)
- Value Objects (e.g., `Amount`, `CpfCnpj`, `Email`, `Phone`, `Address`, `CreditCard`)
- Domain Enums (e.g., `PaymentMethod`, `PaymentStatus`, `GatewayType`, `WebhookEventType`)
- Domain Contracts/Interfaces (e.g., `GatewayInterface`, `HttpClientInterface`)
- Semantic SDK Exceptions (e.g., `SdkException`, `ValidationException`, `GatewayException`)

## Location

Domain files are located in `src/Domain/`:
```
src/Domain/
├── Contracts/
│   ├── GatewayInterface.php
│   └── HttpClientInterface.php
├── Entities/
│   ├── Charge.php
│   ├── Customer.php
│   └── Refund.php
├── ValueObjects/
│   ├── Amount.php
│   ├── CpfCnpj.php
│   ├── Email.php
│   └── CreditCard.php
├── Enums/
│   ├── GatewayType.php
│   ├── PaymentMethod.php
│   ├── PaymentStatus.php
│   └── WebhookEventType.php
└── Exceptions/
    ├── SdkException.php
    ├── ValidationException.php
    ├── GatewayException.php
    └── WebhookVerificationException.php
```

## Namespace

All domain classes use the `GatewaysSdk\Domain\<SubNamespace>` namespace (PSR-4).

```php
namespace GatewaysSdk\Domain\Entities;
namespace GatewaysSdk\Domain\ValueObjects;
namespace GatewaysSdk\Domain\Enums;
namespace GatewaysSdk\Domain\Contracts;
namespace GatewaysSdk\Domain\Exceptions;
```

---

## Entity Pattern

Entities encapsulate core business invariants, identity, and lifecycle state transitions. They are pure PHP (no external dependencies, no framework coupling).

```php
<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Entities;

use DateTimeImmutable;
use GatewaysSdk\Domain\Enums\PaymentMethod;
use GatewaysSdk\Domain\Enums\PaymentStatus;
use GatewaysSdk\Domain\ValueObjects\Amount;

/**
 * Charge Entity representing a transaction in the SDK.
 */
class Charge
{
	private function __construct(
		private readonly string $id,
		private readonly ?string $gatewayId,
		private readonly Amount $amount,
		private readonly PaymentMethod $paymentMethod,
		private PaymentStatus $status,
		private readonly ?Customer $customer,
		private readonly DateTimeImmutable $createdAt,
		private ?DateTimeImmutable $paidAt = null
	) {
	}

	/**
	 * Factory method to instantiate a Charge entity.
	 *
	 * @param string $id Internal SDK identifier
	 * @param string|null $gatewayId Remote gateway identifier
	 * @param Amount $amount Charge monetary amount
	 * @param PaymentMethod $paymentMethod Payment method
	 * @param PaymentStatus $status Initial payment status
	 * @param Customer|null $customer Customer associated with charge
	 * @param DateTimeImmutable|null $createdAt Creation timestamp
	 * @return self
	 */
	public static function create(
		string $id,
		?string $gatewayId,
		Amount $amount,
		PaymentMethod $paymentMethod,
		PaymentStatus $status = PaymentStatus::PENDING,
		?Customer $customer = null,
		?DateTimeImmutable $createdAt = null
	): self {
		return new self(
			$id,
			$gatewayId,
			$amount,
			$paymentMethod,
			$status,
			$customer,
			$createdAt ?? new DateTimeImmutable()
		);
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getGatewayId(): ?string
	{
		return $this->gatewayId;
	}

	public function getAmount(): Amount
	{
		return $this->amount;
	}

	public function getPaymentMethod(): PaymentMethod
	{
		return $this->paymentMethod;
	}

	public function getStatus(): PaymentStatus
	{
		return $this->status;
	}

	public function markAsPaid(DateTimeImmutable $paidAt): void
	{
		$this->status = PaymentStatus::PAID;
		$this->paidAt = $paidAt;
	}
}
```

---

## Value Object Pattern

Value Objects are **immutable**, self-validating, and compare by value. Always implement `__toString()` where suitable.

```php
<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\ValueObjects;

use GatewaysSdk\Domain\Exceptions\ValidationException;

/**
 * Value Object representing a monetary amount in cents (preventing float precision issues).
 */
readonly class Amount
{
	/**
	 * Constructor.
	 *
	 * @param int $cents Amount in integer cents (e.g. 1000 = R$ 10,00)
	 * @param string $currency ISO-4217 currency code (default: BRL)
	 * @throws ValidationException If amount is negative
	 */
	public function __construct(
		public int $cents,
		public string $currency = 'BRL'
	) {
		if ($cents < 0) {
			throw new ValidationException("Amount cannot be negative: {$cents}");
		}
	}

	/**
	 * Create Amount from decimal float (e.g. 19.90 -> 1990 cents).
	 *
	 * @param float $decimal Float representation
	 * @param string $currency Currency code
	 * @return self
	 */
	public static function fromFloat(float $decimal, string $currency = 'BRL'): self
	{
		$cents = (int) round($decimal * 100);
		return new self($cents, $currency);
	}

	public function toFloat(): float
	{
		return $this->cents / 100.0;
	}

	public function formatted(): string
	{
		return number_format($this->toFloat(), 2, ',', '.');
	}

	public function __toString(): string
	{
		return (string) $this->cents;
	}

	public function equals(Amount $other): bool
	{
		return $this->cents === $other->cents && $this->currency === $other->currency;
	}
}
```

---

## Domain Enums Pattern (PHP 8.2 Backed Enums)

Use PHP 8.2 Backed Enums with helper methods to avoid magic strings:

```php
<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Enums;

/**
 * Payment method supported across gateways.
 */
enum PaymentMethod: string
{
	case PIX = 'pix';
	case CREDIT_CARD = 'credit_card';
	case BOLETO = 'boleto';

	public function isInstant(): bool
	{
		return $this === self::PIX;
	}
}
```

---

## Domain Rules & Invariants

1. **Strict Types**: Every file MUST start with `declare(strict_types=1);`.
2. **Domain Isolation**: Domain classes MUST NOT depend on external HTTP libraries, framework code, or gateway-specific schemas.
3. **Semantic Exceptions**: Never throw generic `\RuntimeException` or `\Exception`. Always throw semantic exceptions derived from `SdkException` (e.g. `ValidationException`, `GatewayException`).
4. **Immutability of Value Objects**: Value Objects must be declared `readonly` and contain no setters.
5. **Entity Factory**: Entities use static `create(...)` factory methods enforcing invariants.
6. **Code Style**:
   - Tab indentation (per `.editorconfig`).
   - PSR-12 opening brace on the next line for classes, enums, interfaces, and methods.
   - No single-letter variables (`$index`, `$charge`, `$customer` instead of `$i`, `$c`).
   - English docblocks on all classes and methods.

---

## Anti-Patterns

❌ **Float for Money**: Passing raw floats around for monetary amounts. Always use `Amount` (integer cents) to prevent IEEE 754 floating-point rounding bugs.
❌ **Gateway Leaks in Domain**: Putting Asaas or Pagar.me specific fields (like `billingType`, `asaas_customer_id`, `recipient_id`) inside Domain Entities or Value Objects. Domain must remain completely gateway-agnostic.
❌ **Mutable Value Objects**: Adding setter methods to Value Objects or changing their properties after creation.
❌ **Generic Exceptions**: Throwing `new \Exception("Error")` or `\RuntimeException`.
