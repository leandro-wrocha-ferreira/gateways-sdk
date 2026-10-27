# AGENTS.md / GEMINI.md

## Project: gateways-sdk

**gateways-sdk** is an agnostic, modern PHP 8.2 Payment Gateways SDK designed to centralize and unify payment operations (Asaas and Pagar.me). Consuming applications (e.g. e-commerce, LMS, subscription platforms) integrate with this single SDK rather than writing custom gateway implementations per project.

---

## Architecture: Clean Architecture & Ports & Adapters

The project strictly follows **Clean Architecture / Ports & Adapters (Hexagonal)**:

```
Consuming Application → GatewaysSdk (Facade) / Use Cases → Domain Contracts
                                                                 ▲
                                                                 │ implements
                                                     Infrastructure Gateways (Asaas, Pagarme)
```

| Layer | Directory | Responsibilities |
|---|---|---|
| **Domain** | `src/Domain/` | Entities, Value Objects, Enums, Contracts (`GatewayInterface`, `HttpClientInterface`), Exceptions |
| **Application** | `src/Application/` | Use Cases (`CreateChargeUseCase`, `RefundChargeUseCase`, etc.) and DTOs |
| **Infrastructure** | `src/Infrastructure/` | Gateway Adapters (`AsaasGateway`, `PagarmeGateway`), Mappers, HTTP Client, Factory |
| **Facade** | `src/GatewaysSdk.php` | Fluent, friendly entrypoint for consuming applications |

---

## Architectural Rules

1. **Domain Isolation**:
   - Domain classes MUST NEVER depend on external HTTP libraries, framework code, or vendor-specific data structures.
   - Domain rules, entities, and value objects must be pure PHP 8.2.
   - Money is ALWAYS represented by the `Amount` Value Object (integer cents) to completely avoid IEEE 754 floating-point rounding errors. Never pass raw floats for monetary amounts.

2. **Application Orchestration**:
   - Use Cases coordinate domain entities, validation, and gateway adapters.
   - Dependencies (such as `GatewayInterface`) are strictly injected via the constructor.
   - Single responsibility: One action per Use Case with a standard `execute()` method.
   - Every single Use Case MUST have a corresponding 1:1 unit test in `tests/Unit/Application/UseCases/`.

3. **Infrastructure Encapsulation**:
   - Gateway adapters implement `GatewaysSdk\Domain\Contracts\GatewayInterface`.
   - Vendor-specific payload transformations (e.g. Asaas fields vs Pagar.me fields) are strictly encapsulated inside dedicated Mappers (`AsaasMapper`, `PagarmeMapper`).
   - Gateways communicate via `HttpClientInterface` to allow effortless HTTP mocking during testing without real network calls.
   - Gateway errors are normalized into semantic exceptions (`GatewayException`, `PaymentDeclinedException`).

4. **Prohibition of Architectural Bypasses**:
   - It is strictly forbidden to bypass domain contracts or access gateway-specific response payloads directly from consuming application code.
   - Always map external gateway responses to SDK Domain Entities or `ChargeResultDTO`.

---

## Naming Conventions

| Component | Convention | Example |
|---|---|---|
| Entities | `PascalCase` | `Charge.php`, `Customer.php` |
| Value Objects | `PascalCase` (readonly class) | `Amount.php`, `CpfCnpj.php`, `Email.php` |
| Enums | `PascalCase` (backed string) | `PaymentMethod.php`, `PaymentStatus.php` |
| Contracts | `PascalCase` with `Interface` suffix | `GatewayInterface.php`, `HttpClientInterface.php` |
| Exceptions | `PascalCase` with `Exception` suffix | `SdkException.php`, `GatewayException.php` |
| Use Cases | `VerbNounUseCase.php` | `CreateChargeUseCase.php`, `RefundChargeUseCase.php` |
| DTOs | `PascalCase` with `DTO` suffix | `CreateChargeDTO.php`, `ChargeResultDTO.php` |
| Gateway Adapters | `<Name>Gateway.php` | `AsaasGateway.php`, `PagarmeGateway.php` |
| Mappers | `<Name>Mapper.php` | `AsaasMapper.php`, `PagarmeMapper.php` |
| Configs | `<Name>Config.php` | `AsaasConfig.php`, `PagarmeConfig.php` |

---

## PSR-4 Autoloading

```json
"autoload": {
    "psr-4": {
        "GatewaysSdk\\": "src/"
    }
},
"autoload-dev": {
    "psr-4": {
        "GatewaysSdk\\Tests\\": "tests/"
    }
}
```

---

## Code Style & Conventions

### Strict Types
Every PHP file MUST include `declare(strict_types=1);` at the top.

```php
<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\ValueObjects;
```

### PSR-12 Bracket Style
Opening braces `{` MUST be placed on the **next line** for classes, interfaces, enums, and methods:

```php
class CreateChargeUseCase
{
	public function execute(CreateChargeDTO $dto): ChargeResultDTO
	{
		// ...
	}
}
```

### Tab Indentation
- Use tabs for indentation (per `.editorconfig`). Never use spaces for indentation.
- Line endings: LF. Charset: UTF-8.

### No Vertical Alignment
- Do NOT use multiple spaces to column-align `=>` or `=`. Use exactly one space before and after operators.

### No Single-Letter Variables
- Variable names MUST be descriptive (`$index`, `$charge`, `$gateway`, `$dto` — never `$i`, `$c`, `$g`, `$v`).

### Docblocks
- English docblocks are required on all classes, interfaces, and methods.

### Exceptions
- Throw semantic domain exceptions derived from `GatewaysSdk\Domain\Exceptions\SdkException`.
- Never throw generic `\RuntimeException` or `\Exception`.

---

## Docker & Development Environment

The project provides a lightweight **PHP 8.2 CLI + Composer** container dedicated to executing test suites and verification scripts.

```bash
docker compose run --rm app composer install                 # install dependencies
docker compose run --rm app composer dump-autoload           # regenerate autoloader
docker compose run --rm app vendor/bin/phpunit               # run all tests
docker compose run --rm app vendor/bin/phpunit tests/Unit    # run unit tests only
docker compose run --rm app vendor/bin/phpunit tests/Integration # run integration tests
```

> [!IMPORTANT]
> **CLI Tool Execution**: PHP, Composer, and PHPUnit MUST ALWAYS be executed inside the Docker container (`docker compose run --rm app ...`), NEVER locally on the host.

---

## Deterministic Verification Scripts

The project includes deterministic verification scripts in `.agents/scripts/`:

1. **Conventions Validator**:
   ```bash
   bash .agents/scripts/check-conventions.sh
   bash .agents/scripts/check-conventions.sh --staged
   ```
2. **1:1 Use Case Test Verifier**:
   ```bash
   docker compose run --rm app php .agents/scripts/verify-usecase-tests.php
   ```
3. **Gateway Contract Verifier**:
   ```bash
   docker compose run --rm app php .agents/scripts/verify-gateway-contracts.php
   ```

---

## Version Control & Git Commands

- All Git operations (status, diff, log, add, commit) MUST be executed using `git-leandro`.
- Staging and committing are strictly restricted to the `/git-committer` agent workflow.
- To satisfy the `.agents/hooks.json` safety gatekeeper (`git-guard.py`), mutating commands must be executed as:
  ```bash
  GIT_AGENT=git-committer git-leandro add <files...>
  GIT_AGENT=git-committer git-leandro commit -m "<type>(<scope>): <description>"
  ```
