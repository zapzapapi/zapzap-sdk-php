<?php

declare(strict_types=1);

namespace ZapZap;

use Exception;

/**
 * Thrown when the ZapZap API returns a non-2xx response or a request fails.
 */
class ZapZapException extends Exception
{
    /** @var int|null HTTP status code, or null when the request never completed. */
    private $statusCode;

    /** @var mixed Parsed response body (array/string), or null. */
    private $response;

    /**
     * @param mixed $response
     */
    public function __construct(string $message, ?int $statusCode = null, $response = null)
    {
        parent::__construct($message, $statusCode ?? 0);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
