<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Contracts;

/**
 * HTTP Response contract.
 */
interface HttpResponseInterface
{
	public function getStatusCode(): int;

	public function isSuccessful(): bool;

	public function getBody(): string;

	/**
	 * Parse JSON body into array.
	 *
	 * @return array<string, mixed>
	 */
	public function json(): array;
}
