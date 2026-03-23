<?php declare(strict_types=1);

namespace StellarWP\LicensingApiClientWordPress\Http;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use StellarWP\LicensingApiClientWordPress\Exceptions\WordPressHttpClientException;
use WP_Error;

/**
 * Sends PSR-18 requests through WordPress's HTTP API.
 */
final class WordPressHttpClient implements ClientInterface
{
	/**
	 * @throws WordPressHttpClientException
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface {
		$response = wp_remote_request((string) $request->getUri(), [
			'method'  => $request->getMethod(),
			'headers' => $this->normalizeRequestHeaders($request),
			'body'    => (string) $request->getBody(),
		]);

		if ($response instanceof WP_Error) {
			throw new WordPressHttpClientException($response->get_error_message());
		}

		return new Response(
			(int) wp_remote_retrieve_response_code($response),
			$this->normalizeResponseHeaders($response),
			(string) wp_remote_retrieve_body($response),
			'1.1',
			(string) wp_remote_retrieve_response_message($response)
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function normalizeRequestHeaders(RequestInterface $request): array {
		$headers = [];

		foreach ($request->getHeaders() as $name => $values) {
			if (strtolower($name) === 'host') {
				continue;
			}

			$headers[$name] = implode(', ', $values);
		}

		return $headers;
	}

	/**
	 * WordPress may return headers as either a plain array or a
	 * CaseInsensitiveDictionary from the Requests library.
	 *
	 * @param array<string, mixed> $response
	 *
	 * @return array<string, string>
	 */
	private function normalizeResponseHeaders(array $response): array {
		$headers = [];

		foreach ((array) wp_remote_retrieve_headers($response) as $name => $value) {
			$headers[(string) $name] = is_array($value) ? implode(', ', $value) : (string) $value;
		}

		return $headers;
	}
}
