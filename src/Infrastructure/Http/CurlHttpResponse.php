<?php

declare(strict_types=1);

namespace GatewaysSdk\Infrastructure\Http;

use GatewaysSdk\Domain\Contracts\HttpResponseInterface;

/**
 * Concrete HTTP response implementation.
 */
class CurlHttpResponse implements HttpResponseInterface
{
	/**
	 * Constructor.
	 *
	 * @param int $statusCode HTTP response status code
	 * @param string $body Raw response body string
	 */
	public function __construct(
		private readonly int $statusCode,
		private readonly string $body
	) {
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	public function isSuccessful(): bool
	{
		return $this->statusCode >= 200 && $this->statusCode < 300;
	}

	public function getBody(): string
	{
		return $this->body;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function json(): array
	{
		if (trim($this->body) === '') {
			return [];
		}

		$decoded = json_decode($this->body, true);

		return is_array($decoded) ? $decoded : [];
	}
}
