<?php

declare(strict_types=1);

namespace GatewaysSdk\Domain\Contracts;

/**
 * HTTP Client contract for all gateway adapters.
 */
interface HttpClientInterface
{
	/**
	 * Send a GET request.
	 *
	 * @param string $url Target endpoint URL
	 * @param array<string, string> $headers HTTP request headers
	 * @return HttpResponseInterface
	 */
	public function get(string $url, array $headers = []): HttpResponseInterface;

	/**
	 * Send a POST request.
	 *
	 * @param string $url Target endpoint URL
	 * @param array<string, mixed> $payload Request body data
	 * @param array<string, string> $headers HTTP request headers
	 * @return HttpResponseInterface
	 */
	public function post(string $url, array $payload = [], array $headers = []): HttpResponseInterface;

	/**
	 * Send a DELETE request.
	 *
	 * @param string $url Target endpoint URL
	 * @param array<string, string> $headers HTTP request headers
	 * @return HttpResponseInterface
	 */
	public function delete(string $url, array $headers = []): HttpResponseInterface;
}
