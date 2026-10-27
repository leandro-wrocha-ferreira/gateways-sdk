<?php

declare(strict_types=1);

namespace GatewaysSdk\Infrastructure\Http;

use GatewaysSdk\Domain\Contracts\HttpClientInterface;
use GatewaysSdk\Domain\Contracts\HttpResponseInterface;
use GatewaysSdk\Domain\Exceptions\GatewayException;

/**
 * Lightweight native cURL HTTP client.
 */
class CurlHttpClient implements HttpClientInterface
{
	public function get(string $url, array $headers = []): HttpResponseInterface
	{
		return $this->request('GET', $url, [], $headers);
	}

	public function post(string $url, array $payload = [], array $headers = []): HttpResponseInterface
	{
		return $this->request('POST', $url, $payload, $headers);
	}

	public function delete(string $url, array $headers = []): HttpResponseInterface
	{
		return $this->request('DELETE', $url, [], $headers);
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array<string, mixed> $payload
	 * @param array<string, string> $headers
	 * @return HttpResponseInterface
	 * @throws GatewayException
	 */
	private function request(string $method, string $url, array $payload, array $headers): HttpResponseInterface
	{
		$curlHandle = curl_init();

		$formattedHeaders = [];
		foreach ($headers as $key => $value) {
			$formattedHeaders[] = "{$key}: {$value}";
		}

		$options = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => $formattedHeaders,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => 10,
		];

		if ($method === 'POST') {
			$options[CURLOPT_POSTFIELDS] = json_encode($payload);
		}

		curl_setopt_array($curlHandle, $options);

		$responseBody = curl_exec($curlHandle);
		$statusCode = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

		if ($responseBody === false) {
			$errorMessage = curl_error($curlHandle);
			curl_close($curlHandle);
			throw new GatewayException("HTTP connection failed: {$errorMessage}");
		}

		curl_close($curlHandle);

		return new CurlHttpResponse($statusCode, (string) $responseBody);
	}
}
