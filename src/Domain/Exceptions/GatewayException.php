<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Exceptions;

use Throwable;

/**
 * Exception thrown when a payment gateway returns an error.
 */
class GatewayException extends SdkException
{
	/**
	 * Constructor.
	 *
	 * @param string $message Error description
	 * @param int $statusCode HTTP status code if available
	 * @param array<string, mixed> $rawResponse Raw response payload from gateway
	 * @param Throwable|null $previous Previous exception
	 */
	public function __construct(
		string $message,
		private readonly int $statusCode = 400,
		private readonly array $rawResponse = [],
		?Throwable $previous = null
	) {
		parent::__construct($message, $statusCode, $previous);
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getRawResponse(): array
	{
		return $this->rawResponse;
	}
}
