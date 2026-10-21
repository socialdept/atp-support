<?php

namespace SocialDept\AtpSupport\Microcosm;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

/**
 * A Microcosm request that did not answer. When the service answered with an
 * XRPC error, $error names it and $status is the HTTP status, so a caller can
 * tell "not there" from "the service is down". A missing record is the
 * RecordNotFoundException subclass.
 */
class MicrocosmException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $error = null,
        public readonly ?int $status = null,
    ) {
        parent::__construct($message);
    }

    public static function requestFailed(string $endpoint, string $message, ?string $error = null, ?int $status = null): self
    {
        return new self("Microcosm request to [{$endpoint}] failed: {$message}", $error, $status);
    }

    public static function invalidResponse(string $endpoint): self
    {
        return new self("Microcosm returned an invalid response from [{$endpoint}]");
    }

    /**
     * The exception for a failed request: the XRPC error the service gave, or
     * the transport failure when it gave none.
     */
    public static function fromGuzzle(string $endpoint, GuzzleException $e): self
    {
        $response = $e instanceof RequestException ? $e->getResponse() : null;

        if ($response === null) {
            return self::requestFailed($endpoint, $e->getMessage());
        }

        $body = json_decode((string) $response->getBody(), true);
        $error = is_array($body) && is_string($body['error'] ?? null) ? $body['error'] : null;
        $message = is_array($body) && is_string($body['message'] ?? null) ? $body['message'] : $response->getReasonPhrase();

        return $error === 'RecordNotFound'
            ? new RecordNotFoundException("Microcosm request to [{$endpoint}] failed: {$message}", $error, $response->getStatusCode())
            : self::requestFailed($endpoint, $message, $error, $response->getStatusCode());
    }

    /**
     * Whether the service could not answer at all: no response, or a server
     * error. Worth trying again later, unlike a request it refused.
     */
    public function isUnavailable(): bool
    {
        return $this->status === null || $this->status >= 500;
    }
}
