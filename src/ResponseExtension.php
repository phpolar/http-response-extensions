<?php

declare(strict_types=1);

namespace Phpolar\Extensions\HttpResponse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Extends PSR-7 HTTP response functionality.
 */
final class ResponseExtension implements ResponseInterface
{
    private function __construct(private ResponseInterface $response)
    {
    }

    public function getBody()
    {
        return $this->response->getBody();
    }

    /**
     * @codeCoverageIgnore
     */
    public function getHeader($name)
    {
        return $this->response->getHeader($name);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getHeaderLine($name)
    {
        return $this->response->getHeaderLine($name);
    }

    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    public function getProtocolVersion()
    {
        return $this->response->getProtocolVersion();
    }

    public function getReasonPhrase()
    {
        return $this->response->getReasonPhrase();
    }

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * @codeCoverageIgnore
     */
    public function hasHeader($name)
    {
        return $this->response->hasHeader($name);
    }

    /**
     * @codeCoverageIgnore
     */
    public function withAddedHeader($name, $value)
    {
        return new self($this->response->withAddedHeader($name, $value));
    }

    public function withBody(StreamInterface $body)
    {
        return new self($this->response->withBody($body));
    }

    public function withHeader($name, $value)
    {
        return new self($this->response->withHeader($name, $value));
    }

    /**
     * @codeCoverageIgnore
     */
    public function withoutHeader($name)
    {
        return new self($this->response->withoutHeader($name));
    }

    /**
     * @codeCoverageIgnore
     */
    public function withProtocolVersion($version)
    {
        return new self($this->response->withProtocolVersion($version));
    }

    /**
     * @codeCoverageIgnore
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        return new self($this->response->withStatus($code, $reasonPhrase));
    }

    /**
     * Add custom methods to the PSR-7 Response instance.
     */
    public static function extend(ResponseInterface $response): ResponseInterface&ResponseExtension
    {
        return new self($response);
    }

    /**
     * Send the PSR-7 response.
     */
    public function send(): void
    {
        header(
            sprintf(
                "HTTP/%s %d %s",
                $this->getProtocolVersion(),
                $this->getStatusCode(),
                $this->getReasonPhrase(),
            ),
            true,
            $this->getStatusCode(),
        );
        foreach ($this->getHeaders() as $headerName => $headerValue) {
            header(
                sprintf("%s: %s", $headerName, implode(", ", $headerValue)),
                false,
            );
        }
        echo $this->getBody()->getContents();
    }
}
