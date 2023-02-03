<?php

declare(strict_types=1);

namespace Phpolar\Extensions\HttpResponse;

use Phpolar\Extensions\HttpResponse\Exception\InvalidHeaderNameException;
use Phpolar\Extensions\HttpResponse\Exception\InvalidHeaderValueException;
use Phpolar\HttpCodes\ResponseCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Extends PSR-7 HTTP response functionality.
 */
final class ResponseExtension implements ResponseInterface
{
    const INVALID_HEADER_NAME = "/^[^[:space:]]+$/D";
    const INVALID_HEADER_VALUE = "/^[^[:cntrl:]]+$/D";

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

    /**
     * @codeCoverageIgnore
     */
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
     *
     * @throws InvalidHeaderNameException
     */
    public function send(): void
    {
        match ($this->getProtocolVersion()) {
            "1.0"=> $this->headersSendFirstLineHTTPv1(),
            "1.1"=> $this->headersSendFirstLineHTTPv1_1(),
            "2.0"=> $this->headersSendFirstLineHTTPv2(),
            "3.0"=> $this->headersSendFirstLineHTTPv3(),
            default => $this->headersSendFirstLineHTTPv1_1(),
        };
        foreach ($this->getHeaders() as $headerName => $headerValueArray) {
            $headerValueList = implode(", ", $headerValueArray);
            if (false === $this->headerNameIsInvalid($headerName)) {
                throw new InvalidHeaderNameException();
            }
            if (false === $this->headerValueListIsInvalid($headerValueList)) {
                throw new InvalidHeaderValueException();
            }
            // nosemgrep: php.lang.security.non-literal-header.non-literal-header
            header(
                sprintf("%s: %s", $headerName, $headerValueList),
                false,
            );
        }
        echo $this->getBody()->getContents();
    }

    private function headerNameIsInvalid(string $headerName): bool
    {
        return filter_var(
            $headerName,
            FILTER_VALIDATE_REGEXP,
            ["options" => ["regexp" => self::INVALID_HEADER_NAME]]
        ) !== false;
    }

    private function headerValueListIsInvalid(string $headerValueList): bool
    {
        return filter_var(
            $headerValueList,
            FILTER_VALIDATE_REGEXP,
            ["options" => ["regexp" => self::INVALID_HEADER_VALUE]]
        ) !== false;
    }

    /**
     * Use literal strings for headers.
     *
     * @see https://www.php.net/manual/en/function.header.php
     * @see https://owasp.org/www-community/attacks/HTTP_Response_Splitting
     */
    private function headersSendFirstLineHTTPv1(): void
    {
        match ($code = $this->getStatusCode()) {
            ResponseCode::ACCEPTED => header("HTTP/1.0 202 Accepted", true, ResponseCode::ACCEPTED),
            ResponseCode::ALREADY_REPORTED => header("HTTP/1.0 208 Already Reported", true, ResponseCode::ALREADY_REPORTED),
            ResponseCode::BAD_GATEWAY => header("HTTP/1.0 502 Bad Gateway", true, ResponseCode::BAD_GATEWAY),
            ResponseCode::BAD_REQUEST => header("HTTP/1.0 400 Bad Request", true, ResponseCode::BAD_REQUEST),
            ResponseCode::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS => header("HTTP/1.0 450 Blocked", true, ResponseCode::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS),
            ResponseCode::EARLY_HINTS => header("HTTP/1.0 103 Early Hints", true, ResponseCode::EARLY_HINTS),
            ResponseCode::CONTINUE => header("HTTP/1.0 100 Continue", true, ResponseCode::CONTINUE),
            ResponseCode::CONFLICT => header("HTTP/1.0 409 Conflict", true, ResponseCode::CONFLICT),
            ResponseCode::CREATED => header("HTTP/1.0 201 Created", true, ResponseCode::CREATED),
            ResponseCode::ENHANCE_YOUR_CALM => header("HTTP/1.0 420 Calm", true, ResponseCode::ENHANCE_YOUR_CALM),
            ResponseCode::EXPECTATION_FAILED => header("HTTP/1.0 417 Expectation Failed", true, ResponseCode::EXPECTATION_FAILED),
            ResponseCode::FAILED_DEPENDENCY => header("HTTP/1.0 424 Failed Dependency", true, ResponseCode::FAILED_DEPENDENCY),
            ResponseCode::FORBIDDEN => header("HTTP/1.0 403 Forbidden", true, ResponseCode::FORBIDDEN),
            ResponseCode::FOUND => header("HTTP/1.0 302 Found", true, ResponseCode::FOUND),
            ResponseCode::GATEWAY_TIMEOUT => header("HTTP/1.0 504 Gateway Timeout", true, ResponseCode::GATEWAY_TIMEOUT),
            ResponseCode::GONE => header("HTTP/1.0 410 Gone", true, ResponseCode::GONE),
            ResponseCode::HTTP_VERSION_NOT_SUPPORTED => header("HTTP/1.0 505 HTTP Version Not Supported", true, ResponseCode::HTTP_VERSION_NOT_SUPPORTED),
            ResponseCode::IM_A_TEAPOT => header("HTTP/1.0 418 I'm a teapot", true, ResponseCode::IM_A_TEAPOT),
            ResponseCode::IM_USED => header("HTTP/1.0 226 I'm used", true, ResponseCode::IM_USED),
            ResponseCode::INSUFFICIENT_STORAGE => header("HTTP/1.0 507 Insufficient Storage", true, ResponseCode::INSUFFICIENT_STORAGE),
            ResponseCode::INTERNAL_SERVER_ERROR => header("HTTP/1.0 500 Internal Server Error", true, ResponseCode::INTERNAL_SERVER_ERROR),
            ResponseCode::INVALID_TOKEN => header("HTTP/1.0 498 Invalid Token", true, ResponseCode::INVALID_TOKEN),
            ResponseCode::LENGTH_REQUIRED => header("HTTP/1.0 411 Length Required", true, ResponseCode::LENGTH_REQUIRED),
            ResponseCode::LOCKED => header("HTTP/1.0 423 Locked", true, ResponseCode::LOCKED),
            ResponseCode::LOOP_DETECTED => header("HTTP/1.0 508 Loop Detected", true, ResponseCode::LOOP_DETECTED),
            ResponseCode::METHOD_FAILURE => header("HTTP/1.0 420 Method Failure", true, ResponseCode::METHOD_FAILURE),
            ResponseCode::METHOD_NOT_ALLLOWED => header("HTTP/1.0 405 Method Not Allowed", true, ResponseCode::METHOD_NOT_ALLLOWED),
            ResponseCode::MISDIRECTED_REQUEST => header("HTTP/1.0 421 Misdirected Request", true, ResponseCode::MISDIRECTED_REQUEST),
            ResponseCode::MOVED_PERMANENTLY => header("HTTP/1.0 301 Moved Permanently", true, ResponseCode::MOVED_PERMANENTLY),
            ResponseCode::MULTI_STATUS => header("HTTP/1.0 207 Multi Status", true, ResponseCode::MULTI_STATUS),
            ResponseCode::MULTIPLE_CHOICES => header("HTTP/1.0 300 Multiple Choices", true, ResponseCode::MULTIPLE_CHOICES),
            ResponseCode::NETWORK_AUTHENTICATION_REQUIRED => header("HTTP/1.0 511 Network Authentication Required", true, ResponseCode::NETWORK_AUTHENTICATION_REQUIRED),
            ResponseCode::NO_CONTENT => header("HTTP/1.0 204 No Content", true, ResponseCode::NO_CONTENT),
            ResponseCode::NON_AUTH => header("HTTP/1.0 203 Non-Authoritative Information", true, ResponseCode::NON_AUTH),
            ResponseCode::NOT_ACCEPTABLE => header("HTTP/1.0 406 Not Acceptable", true, ResponseCode::NOT_ACCEPTABLE),
            ResponseCode::NOT_EXTENDED => header("HTTP/1.0 510 Not Extended", true, ResponseCode::NOT_EXTENDED),
            ResponseCode::NOT_FOUND => header("HTTP/1.0 404 Not Found", true, ResponseCode::NOT_FOUND),
            ResponseCode::NOT_IMPLEMENTED => header("HTTP/1.0 501 Not Implemented", true, ResponseCode::NOT_IMPLEMENTED),
            ResponseCode::NOT_MODIFIED => header("HTTP/1.0 304 Not Modified", true, ResponseCode::NOT_MODIFIED),
            ResponseCode::OK => header("HTTP/1.0 200 OK", true, ResponseCode::OK),
            ResponseCode::PAGE_EXPIRED => header("HTTP/1.0 419 Page Expired", true, ResponseCode::PAGE_EXPIRED),
            ResponseCode::PARTIAL_CONTENT => header("HTTP/1.0 206 Partial Content", true, ResponseCode::PARTIAL_CONTENT),
            ResponseCode::PAYLOAD_TOO_LARGE => header("HTTP/1.0 413 Payload Too Large", true, ResponseCode::PAYLOAD_TOO_LARGE),
            ResponseCode::PAYMENT_REQUIRED => header("HTTP/1.0 402 Payment Required", true, ResponseCode::PAYMENT_REQUIRED),
            ResponseCode::PERMANENT_REDIRECT => header("HTTP/1.0 308 Permanent Redirect", true, ResponseCode::PAYMENT_REQUIRED),
            ResponseCode::PRECONDITION_FAILED => header("HTTP/1.0 412 Precondition Failed", true, ResponseCode::PRECONDITION_FAILED),
            ResponseCode::PRECONDITION_REQUIRED => header("HTTP/1.0 428 Precondition Required", true, ResponseCode::PRECONDITION_REQUIRED),
            ResponseCode::PROCESSING => header("HTTP/1.0 102 Processing", true, ResponseCode::PROCESSING),
            ResponseCode::PROXY_AUTHENTICATION_REQUIRED => header("HTTP/1.0 407 Proxy Authentication Required", true, ResponseCode::PROXY_AUTHENTICATION_REQUIRED),
            ResponseCode::RANGE_NOT_SATISFIABLE => header("HTTP/1.0 416 Range Not Satisfiable", true, ResponseCode::RANGE_NOT_SATISFIABLE),
            ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE => header("HTTP/1.0 431 Request Header Fields Too Large", true, ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE),
            ResponseCode::REQUEST_TIMEOUT => header("HTTP/1.0 408 Request Timeout", true, ResponseCode::REQUEST_TIMEOUT),
            ResponseCode::RESET_CONTENT => header("HTTP/1.0 205 Reset Content", true, ResponseCode::RESET_CONTENT),
            ResponseCode::SEE_OTHER => header("HTTP/1.0 303 See Other", true, ResponseCode::SEE_OTHER),
            ResponseCode::SERVICE_UNAVAILABLE => header("HTTP/1.0 503 Service Unavailable", true, ResponseCode::SERVICE_UNAVAILABLE),
            ResponseCode::SWITCH_PROXY => header("HTTP/1.0 306 Switching Proxy", true, ResponseCode::SWITCH_PROXY),
            ResponseCode::SWITCHING_PROTOCOLS => header("HTTP/1.0 101 Switching Protocols", true, ResponseCode::SWITCHING_PROTOCOLS),
            ResponseCode::TEMPORARY_REDIRECT => header("HTTP/1.0 307 Temporary Redirect", true, ResponseCode::TEMPORARY_REDIRECT),
            ResponseCode::TOKEN_REQUIRED => header("HTTP/1.0 499 Token Required", true, ResponseCode::TOKEN_REQUIRED),
            ResponseCode::TOO_EARLY => header("HTTP/1.0 425 Too Early", true, ResponseCode::TOO_EARLY),
            ResponseCode::TOO_MANY_REQUESTS => header("HTTP/1.0 429 Too Many Requests", true, ResponseCode::TOO_MANY_REQUESTS),
            ResponseCode::UNAUTHORIZED => header("HTTP/1.0 401 Unauthorized", true, ResponseCode::UNAUTHORIZED),
            ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS => header("HTTP/1.0 451 Unavailable For Legal Reasons", true, ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS),
            ResponseCode::UNPROCESSABLE_ENTITY => header("HTTP/1.0 422 Unprocessable Entity", true, ResponseCode::UNPROCESSABLE_ENTITY),
            ResponseCode::UNSUPPORTED_MEDIA_TYPE => header("HTTP/1.0 415 Unsupported Media Type", true, ResponseCode::UNSUPPORTED_MEDIA_TYPE),
            ResponseCode::UPGRADE_REQUIRED => header("HTTP/1.0 426 Upgrade Required", true, ResponseCode::UPGRADE_REQUIRED),
            ResponseCode::URI_TOO_LONG => header("HTTP/1.0 414 URI Too Long", true, ResponseCode::URI_TOO_LONG),
            ResponseCode::USE_PROXY => header("HTTP/1.0 305 Use Proxy", true, ResponseCode::USE_PROXY),
            ResponseCode::VARIANT_ALSO_NEGOTIATES => header("HTTP/1.0 506 Variant Also Negotiates", true, ResponseCode::VARIANT_ALSO_NEGOTIATES),
            default => throw new RuntimeException("Response code: {$code} not implemented"),
        };
    }

    /**
     * Use literal strings for headers.
     *
     * @see https://www.php.net/manual/en/function.header.php
     * @see https://owasp.org/www-community/attacks/HTTP_Response_Splitting
     */
    private function headersSendFirstLineHTTPv1_1(): void
    {
        match ($code = $this->getStatusCode()) {
            ResponseCode::ACCEPTED => header("HTTP/1.1 202 Accepted", true, ResponseCode::ACCEPTED),
            ResponseCode::ALREADY_REPORTED => header("HTTP/1.1 208 Already Reported", true, ResponseCode::ALREADY_REPORTED),
            ResponseCode::BAD_GATEWAY => header("HTTP/1.1 502 Bad Gateway", true, ResponseCode::BAD_GATEWAY),
            ResponseCode::BAD_REQUEST => header("HTTP/1.1 400 Bad Request", true, ResponseCode::BAD_REQUEST),
            ResponseCode::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS => header("HTTP/1.1 450 Blocked", true, ResponseCode::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS),
            ResponseCode::EARLY_HINTS => header("HTTP/1.1 103 Early Hints", true, ResponseCode::EARLY_HINTS),
            ResponseCode::CONTINUE => header("HTTP/1.1 100 Continue", true, ResponseCode::CONTINUE),
            ResponseCode::CONFLICT => header("HTTP/1.1 409 Conflict", true, ResponseCode::CONFLICT),
            ResponseCode::CREATED => header("HTTP/1.1 201 Created", true, ResponseCode::CREATED),
            ResponseCode::ENHANCE_YOUR_CALM => header("HTTP/1.1 420 Calm", true, ResponseCode::ENHANCE_YOUR_CALM),
            ResponseCode::EXPECTATION_FAILED => header("HTTP/1.1 417 Expectation Failed", true, ResponseCode::EXPECTATION_FAILED),
            ResponseCode::FAILED_DEPENDENCY => header("HTTP/1.1 424 Failed Dependency", true, ResponseCode::FAILED_DEPENDENCY),
            ResponseCode::FORBIDDEN => header("HTTP/1.1 403 Forbidden", true, ResponseCode::FORBIDDEN),
            ResponseCode::FOUND => header("HTTP/1.1 302 Found", true, ResponseCode::FOUND),
            ResponseCode::GATEWAY_TIMEOUT => header("HTTP/1.1 504 Gateway Timeout", true, ResponseCode::GATEWAY_TIMEOUT),
            ResponseCode::GONE => header("HTTP/1.1 410 Gone", true, ResponseCode::GONE),
            ResponseCode::HTTP_VERSION_NOT_SUPPORTED => header("HTTP/1.1 505 HTTP Version Not Supported", true, ResponseCode::HTTP_VERSION_NOT_SUPPORTED),
            ResponseCode::IM_A_TEAPOT => header("HTTP/1.1 418 I'm a teapot", true, ResponseCode::IM_A_TEAPOT),
            ResponseCode::IM_USED => header("HTTP/1.1 226 I'm used", true, ResponseCode::IM_USED),
            ResponseCode::INSUFFICIENT_STORAGE => header("HTTP/1.1 507 Insufficient Storage", true, ResponseCode::INSUFFICIENT_STORAGE),
            ResponseCode::INTERNAL_SERVER_ERROR => header("HTTP/1.1 500 Internal Server Error", true, ResponseCode::INTERNAL_SERVER_ERROR),
            ResponseCode::INVALID_TOKEN => header("HTTP/1.1 498 Invalid Token", true, ResponseCode::INVALID_TOKEN),
            ResponseCode::LENGTH_REQUIRED => header("HTTP/1.1 411 Length Required", true, ResponseCode::LENGTH_REQUIRED),
            ResponseCode::LOCKED => header("HTTP/1.1 423 Locked", true, ResponseCode::LOCKED),
            ResponseCode::LOOP_DETECTED => header("HTTP/1.1 508 Loop Detected", true, ResponseCode::LOOP_DETECTED),
            ResponseCode::METHOD_FAILURE => header("HTTP/1.1 420 Method Failure", true, ResponseCode::METHOD_FAILURE),
            ResponseCode::METHOD_NOT_ALLLOWED => header("HTTP/1.1 405 Method Not Allowed", true, ResponseCode::METHOD_NOT_ALLLOWED),
            ResponseCode::MISDIRECTED_REQUEST => header("HTTP/1.1 421 Misdirected Request", true, ResponseCode::MISDIRECTED_REQUEST),
            ResponseCode::MOVED_PERMANENTLY => header("HTTP/1.1 301 Moved Permanently", true, ResponseCode::MOVED_PERMANENTLY),
            ResponseCode::MULTI_STATUS => header("HTTP/1.1 207 Multi Status", true, ResponseCode::MULTI_STATUS),
            ResponseCode::MULTIPLE_CHOICES => header("HTTP/1.1 300 Multiple Choices", true, ResponseCode::MULTIPLE_CHOICES),
            ResponseCode::NETWORK_AUTHENTICATION_REQUIRED => header("HTTP/1.1 511 Network Authentication Required", true, ResponseCode::NETWORK_AUTHENTICATION_REQUIRED),
            ResponseCode::NO_CONTENT => header("HTTP/1.1 204 No Content", true, ResponseCode::NO_CONTENT),
            ResponseCode::NON_AUTH => header("HTTP/1.1 203 Non-Authoritative Information", true, ResponseCode::NON_AUTH),
            ResponseCode::NOT_ACCEPTABLE => header("HTTP/1.1 406 Not Acceptable", true, ResponseCode::NOT_ACCEPTABLE),
            ResponseCode::NOT_EXTENDED => header("HTTP/1.1 510 Not Extended", true, ResponseCode::NOT_EXTENDED),
            ResponseCode::NOT_FOUND => header("HTTP/1.1 404 Not Found", true, ResponseCode::NOT_FOUND),
            ResponseCode::NOT_IMPLEMENTED => header("HTTP/1.1 501 Not Implemented", true, ResponseCode::NOT_IMPLEMENTED),
            ResponseCode::NOT_MODIFIED => header("HTTP/1.1 304 Not Modified", true, ResponseCode::NOT_MODIFIED),
            ResponseCode::OK => header("HTTP/1.1 200 OK", true, ResponseCode::OK),
            ResponseCode::PAGE_EXPIRED => header("HTTP/1.1 419 Page Expired", true, ResponseCode::PAGE_EXPIRED),
            ResponseCode::PARTIAL_CONTENT => header("HTTP/1.1 206 Partial Content", true, ResponseCode::PARTIAL_CONTENT),
            ResponseCode::PAYLOAD_TOO_LARGE => header("HTTP/1.1 413 Payload Too Large", true, ResponseCode::PAYLOAD_TOO_LARGE),
            ResponseCode::PAYMENT_REQUIRED => header("HTTP/1.1 402 Payment Required", true, ResponseCode::PAYMENT_REQUIRED),
            ResponseCode::PERMANENT_REDIRECT => header("HTTP/1.1 308 Permanent Redirect", true, ResponseCode::PAYMENT_REQUIRED),
            ResponseCode::PRECONDITION_FAILED => header("HTTP/1.1 412 Precondition Failed", true, ResponseCode::PRECONDITION_FAILED),
            ResponseCode::PRECONDITION_REQUIRED => header("HTTP/1.1 428 Precondition Required", true, ResponseCode::PRECONDITION_REQUIRED),
            ResponseCode::PROCESSING => header("HTTP/1.1 102 Processing", true, ResponseCode::PROCESSING),
            ResponseCode::PROXY_AUTHENTICATION_REQUIRED => header("HTTP/1.1 407 Proxy Authentication Required", true, ResponseCode::PROXY_AUTHENTICATION_REQUIRED),
            ResponseCode::RANGE_NOT_SATISFIABLE => header("HTTP/1.1 416 Range Not Satisfiable", true, ResponseCode::RANGE_NOT_SATISFIABLE),
            ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE => header("HTTP/1.1 431 Request Header Fields Too Large", true, ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE),
            ResponseCode::REQUEST_TIMEOUT => header("HTTP/1.1 408 Request Timeout", true, ResponseCode::REQUEST_TIMEOUT),
            ResponseCode::RESET_CONTENT => header("HTTP/1.1 205 Reset Content", true, ResponseCode::RESET_CONTENT),
            ResponseCode::SEE_OTHER => header("HTTP/1.1 303 See Other", true, ResponseCode::SEE_OTHER),
            ResponseCode::SERVICE_UNAVAILABLE => header("HTTP/1.1 503 Service Unavailable", true, ResponseCode::SERVICE_UNAVAILABLE),
            ResponseCode::SWITCH_PROXY => header("HTTP/1.1 306 Switching Proxy", true, ResponseCode::SWITCH_PROXY),
            ResponseCode::SWITCHING_PROTOCOLS => header("HTTP/1.1 101 Switching Protocols", true, ResponseCode::SWITCHING_PROTOCOLS),
            ResponseCode::TEMPORARY_REDIRECT => header("HTTP/1.1 307 Temporary Redirect", true, ResponseCode::TEMPORARY_REDIRECT),
            ResponseCode::TOKEN_REQUIRED => header("HTTP/1.1 499 Token Required", true, ResponseCode::TOKEN_REQUIRED),
            ResponseCode::TOO_EARLY => header("HTTP/1.1 425 Too Early", true, ResponseCode::TOO_EARLY),
            ResponseCode::TOO_MANY_REQUESTS => header("HTTP/1.1 429 Too Many Requests", true, ResponseCode::TOO_MANY_REQUESTS),
            ResponseCode::UNAUTHORIZED => header("HTTP/1.1 401 Unauthorized", true, ResponseCode::UNAUTHORIZED),
            ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS => header("HTTP/1.1 451 Unavailable For Legal Reasons", true, ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS),
            ResponseCode::UNPROCESSABLE_ENTITY => header("HTTP/1.1 422 Unprocessable Entity", true, ResponseCode::UNPROCESSABLE_ENTITY),
            ResponseCode::UNSUPPORTED_MEDIA_TYPE => header("HTTP/1.1 415 Unsupported Media Type", true, ResponseCode::UNSUPPORTED_MEDIA_TYPE),
            ResponseCode::UPGRADE_REQUIRED => header("HTTP/1.1 426 Upgrade Required", true, ResponseCode::UPGRADE_REQUIRED),
            ResponseCode::URI_TOO_LONG => header("HTTP/1.1 414 URI Too Long", true, ResponseCode::URI_TOO_LONG),
            ResponseCode::USE_PROXY => header("HTTP/1.1 305 Use Proxy", true, ResponseCode::USE_PROXY),
            ResponseCode::VARIANT_ALSO_NEGOTIATES => header("HTTP/1.1 506 Variant Also Negotiates", true, ResponseCode::VARIANT_ALSO_NEGOTIATES),
            default => throw new RuntimeException("Response code: {$code} not implemented"),
        };
    }

    /**
     * Use literal strings for headers.
     *
     * @see https://www.php.net/manual/en/function.header.php
     * @see https://owasp.org/www-community/attacks/HTTP_Response_Splitting
     */
    private function headersSendFirstLineHTTPv2(): void
    {
        match ($code = $this->getStatusCode()) {
            ResponseCode::ACCEPTED => header("HTTP/2.0 202 Accepted", true, ResponseCode::ACCEPTED),
            ResponseCode::ALREADY_REPORTED => header("HTTP/2.0 208 Already Reported", true, ResponseCode::ALREADY_REPORTED),
            ResponseCode::BAD_GATEWAY => header("HTTP/2.0 502 Bad Gateway", true, ResponseCode::BAD_GATEWAY),
            ResponseCode::BAD_REQUEST => header("HTTP/2.0 400 Bad Request", true, ResponseCode::BAD_REQUEST),
            ResponseCode::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS => header("HTTP/2.0 450 Blocked", true, ResponseCode::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS),
            ResponseCode::EARLY_HINTS => header("HTTP/2.0 103 Early Hints", true, ResponseCode::EARLY_HINTS),
            ResponseCode::CONTINUE => header("HTTP/2.0 100 Continue", true, ResponseCode::CONTINUE),
            ResponseCode::CONFLICT => header("HTTP/2.0 409 Conflict", true, ResponseCode::CONFLICT),
            ResponseCode::CREATED => header("HTTP/2.0 201 Created", true, ResponseCode::CREATED),
            ResponseCode::ENHANCE_YOUR_CALM => header("HTTP/2.0 420 Calm", true, ResponseCode::ENHANCE_YOUR_CALM),
            ResponseCode::EXPECTATION_FAILED => header("HTTP/2.0 417 Expectation Failed", true, ResponseCode::EXPECTATION_FAILED),
            ResponseCode::FAILED_DEPENDENCY => header("HTTP/2.0 424 Failed Dependency", true, ResponseCode::FAILED_DEPENDENCY),
            ResponseCode::FORBIDDEN => header("HTTP/2.0 403 Forbidden", true, ResponseCode::FORBIDDEN),
            ResponseCode::FOUND => header("HTTP/2.0 302 Found", true, ResponseCode::FOUND),
            ResponseCode::GATEWAY_TIMEOUT => header("HTTP/2.0 504 Gateway Timeout", true, ResponseCode::GATEWAY_TIMEOUT),
            ResponseCode::GONE => header("HTTP/2.0 410 Gone", true, ResponseCode::GONE),
            ResponseCode::HTTP_VERSION_NOT_SUPPORTED => header("HTTP/2.0 505 HTTP Version Not Supported", true, ResponseCode::HTTP_VERSION_NOT_SUPPORTED),
            ResponseCode::IM_A_TEAPOT => header("HTTP/2.0 418 I'm a teapot", true, ResponseCode::IM_A_TEAPOT),
            ResponseCode::IM_USED => header("HTTP/2.0 226 I'm used", true, ResponseCode::IM_USED),
            ResponseCode::INSUFFICIENT_STORAGE => header("HTTP/2.0 507 Insufficient Storage", true, ResponseCode::INSUFFICIENT_STORAGE),
            ResponseCode::INTERNAL_SERVER_ERROR => header("HTTP/2.0 500 Internal Server Error", true, ResponseCode::INTERNAL_SERVER_ERROR),
            ResponseCode::INVALID_TOKEN => header("HTTP/2.0 498 Invalid Token", true, ResponseCode::INVALID_TOKEN),
            ResponseCode::LENGTH_REQUIRED => header("HTTP/2.0 411 Length Required", true, ResponseCode::LENGTH_REQUIRED),
            ResponseCode::LOCKED => header("HTTP/2.0 423 Locked", true, ResponseCode::LOCKED),
            ResponseCode::LOOP_DETECTED => header("HTTP/2.0 508 Loop Detected", true, ResponseCode::LOOP_DETECTED),
            ResponseCode::METHOD_FAILURE => header("HTTP/2.0 420 Method Failure", true, ResponseCode::METHOD_FAILURE),
            ResponseCode::METHOD_NOT_ALLLOWED => header("HTTP/2.0 405 Method Not Allowed", true, ResponseCode::METHOD_NOT_ALLLOWED),
            ResponseCode::MISDIRECTED_REQUEST => header("HTTP/2.0 421 Misdirected Request", true, ResponseCode::MISDIRECTED_REQUEST),
            ResponseCode::MOVED_PERMANENTLY => header("HTTP/2.0 301 Moved Permanently", true, ResponseCode::MOVED_PERMANENTLY),
            ResponseCode::MULTI_STATUS => header("HTTP/2.0 207 Multi Status", true, ResponseCode::MULTI_STATUS),
            ResponseCode::MULTIPLE_CHOICES => header("HTTP/2.0 300 Multiple Choices", true, ResponseCode::MULTIPLE_CHOICES),
            ResponseCode::NETWORK_AUTHENTICATION_REQUIRED => header("HTTP/2.0 511 Network Authentication Required", true, ResponseCode::NETWORK_AUTHENTICATION_REQUIRED),
            ResponseCode::NO_CONTENT => header("HTTP/2.0 204 No Content", true, ResponseCode::NO_CONTENT),
            ResponseCode::NON_AUTH => header("HTTP/2.0 203 Non-Authoritative Information", true, ResponseCode::NON_AUTH),
            ResponseCode::NOT_ACCEPTABLE => header("HTTP/2.0 406 Not Acceptable", true, ResponseCode::NOT_ACCEPTABLE),
            ResponseCode::NOT_EXTENDED => header("HTTP/2.0 510 Not Extended", true, ResponseCode::NOT_EXTENDED),
            ResponseCode::NOT_FOUND => header("HTTP/2.0 404 Not Found", true, ResponseCode::NOT_FOUND),
            ResponseCode::NOT_IMPLEMENTED => header("HTTP/2.0 501 Not Implemented", true, ResponseCode::NOT_IMPLEMENTED),
            ResponseCode::NOT_MODIFIED => header("HTTP/2.0 304 Not Modified", true, ResponseCode::NOT_MODIFIED),
            ResponseCode::OK => header("HTTP/2.0 200 OK", true, ResponseCode::OK),
            ResponseCode::PAGE_EXPIRED => header("HTTP/2.0 419 Page Expired", true, ResponseCode::PAGE_EXPIRED),
            ResponseCode::PARTIAL_CONTENT => header("HTTP/2.0 206 Partial Content", true, ResponseCode::PARTIAL_CONTENT),
            ResponseCode::PAYLOAD_TOO_LARGE => header("HTTP/2.0 413 Payload Too Large", true, ResponseCode::PAYLOAD_TOO_LARGE),
            ResponseCode::PAYMENT_REQUIRED => header("HTTP/2.0 402 Payment Required", true, ResponseCode::PAYMENT_REQUIRED),
            ResponseCode::PERMANENT_REDIRECT => header("HTTP/2.0 308 Permanent Redirect", true, ResponseCode::PAYMENT_REQUIRED),
            ResponseCode::PRECONDITION_FAILED => header("HTTP/2.0 412 Precondition Failed", true, ResponseCode::PRECONDITION_FAILED),
            ResponseCode::PRECONDITION_REQUIRED => header("HTTP/2.0 428 Precondition Required", true, ResponseCode::PRECONDITION_REQUIRED),
            ResponseCode::PROCESSING => header("HTTP/2.0 102 Processing", true, ResponseCode::PROCESSING),
            ResponseCode::PROXY_AUTHENTICATION_REQUIRED => header("HTTP/2.0 407 Proxy Authentication Required", true, ResponseCode::PROXY_AUTHENTICATION_REQUIRED),
            ResponseCode::RANGE_NOT_SATISFIABLE => header("HTTP/2.0 416 Range Not Satisfiable", true, ResponseCode::RANGE_NOT_SATISFIABLE),
            ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE => header("HTTP/2.0 431 Request Header Fields Too Large", true, ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE),
            ResponseCode::REQUEST_TIMEOUT => header("HTTP/2.0 408 Request Timeout", true, ResponseCode::REQUEST_TIMEOUT),
            ResponseCode::RESET_CONTENT => header("HTTP/2.0 205 Reset Content", true, ResponseCode::RESET_CONTENT),
            ResponseCode::SEE_OTHER => header("HTTP/2.0 303 See Other", true, ResponseCode::SEE_OTHER),
            ResponseCode::SERVICE_UNAVAILABLE => header("HTTP/2.0 503 Service Unavailable", true, ResponseCode::SERVICE_UNAVAILABLE),
            ResponseCode::SWITCH_PROXY => header("HTTP/2.0 306 Switching Proxy", true, ResponseCode::SWITCH_PROXY),
            ResponseCode::SWITCHING_PROTOCOLS => header("HTTP/2.0 101 Switching Protocols", true, ResponseCode::SWITCHING_PROTOCOLS),
            ResponseCode::TEMPORARY_REDIRECT => header("HTTP/2.0 307 Temporary Redirect", true, ResponseCode::TEMPORARY_REDIRECT),
            ResponseCode::TOKEN_REQUIRED => header("HTTP/2.0 499 Token Required", true, ResponseCode::TOKEN_REQUIRED),
            ResponseCode::TOO_EARLY => header("HTTP/2.0 425 Too Early", true, ResponseCode::TOO_EARLY),
            ResponseCode::TOO_MANY_REQUESTS => header("HTTP/2.0 429 Too Many Requests", true, ResponseCode::TOO_MANY_REQUESTS),
            ResponseCode::UNAUTHORIZED => header("HTTP/2.0 401 Unauthorized", true, ResponseCode::UNAUTHORIZED),
            ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS => header("HTTP/2.0 451 Unavailable For Legal Reasons", true, ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS),
            ResponseCode::UNPROCESSABLE_ENTITY => header("HTTP/2.0 422 Unprocessable Entity", true, ResponseCode::UNPROCESSABLE_ENTITY),
            ResponseCode::UNSUPPORTED_MEDIA_TYPE => header("HTTP/2.0 415 Unsupported Media Type", true, ResponseCode::UNSUPPORTED_MEDIA_TYPE),
            ResponseCode::UPGRADE_REQUIRED => header("HTTP/2.0 426 Upgrade Required", true, ResponseCode::UPGRADE_REQUIRED),
            ResponseCode::URI_TOO_LONG => header("HTTP/2.0 414 URI Too Long", true, ResponseCode::URI_TOO_LONG),
            ResponseCode::USE_PROXY => header("HTTP/2.0 305 Use Proxy", true, ResponseCode::USE_PROXY),
            ResponseCode::VARIANT_ALSO_NEGOTIATES => header("HTTP/2.0 506 Variant Also Negotiates", true, ResponseCode::VARIANT_ALSO_NEGOTIATES),
            default => throw new RuntimeException("Response code: {$code} not implemented"),
        };
    }

    /**
     * Use literal strings for headers.
     *
     * @see https://www.php.net/manual/en/function.header.php
     * @see https://owasp.org/www-community/attacks/HTTP_Response_Splitting
     */
    private function headersSendFirstLineHTTPv3(): void
    {
        match ($code = $this->getStatusCode()) {
            ResponseCode::ACCEPTED => header("HTTP/3.0 202 Accepted", true, ResponseCode::ACCEPTED),
            ResponseCode::ALREADY_REPORTED => header("HTTP/3.0 208 Already Reported", true, ResponseCode::ALREADY_REPORTED),
            ResponseCode::BAD_GATEWAY => header("HTTP/3.0 502 Bad Gateway", true, ResponseCode::BAD_GATEWAY),
            ResponseCode::BAD_REQUEST => header("HTTP/3.0 400 Bad Request", true, ResponseCode::BAD_REQUEST),
            ResponseCode::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS => header("HTTP/3.0 450 Blocked", true, ResponseCode::BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS),
            ResponseCode::EARLY_HINTS => header("HTTP/3.0 103 Early Hints", true, ResponseCode::EARLY_HINTS),
            ResponseCode::CONTINUE => header("HTTP/3.0 100 Continue", true, ResponseCode::CONTINUE),
            ResponseCode::CONFLICT => header("HTTP/3.0 409 Conflict", true, ResponseCode::CONFLICT),
            ResponseCode::CREATED => header("HTTP/3.0 201 Created", true, ResponseCode::CREATED),
            ResponseCode::ENHANCE_YOUR_CALM => header("HTTP/3.0 420 Calm", true, ResponseCode::ENHANCE_YOUR_CALM),
            ResponseCode::EXPECTATION_FAILED => header("HTTP/3.0 417 Expectation Failed", true, ResponseCode::EXPECTATION_FAILED),
            ResponseCode::FAILED_DEPENDENCY => header("HTTP/3.0 424 Failed Dependency", true, ResponseCode::FAILED_DEPENDENCY),
            ResponseCode::FORBIDDEN => header("HTTP/3.0 403 Forbidden", true, ResponseCode::FORBIDDEN),
            ResponseCode::FOUND => header("HTTP/3.0 302 Found", true, ResponseCode::FOUND),
            ResponseCode::GATEWAY_TIMEOUT => header("HTTP/3.0 504 Gateway Timeout", true, ResponseCode::GATEWAY_TIMEOUT),
            ResponseCode::GONE => header("HTTP/3.0 410 Gone", true, ResponseCode::GONE),
            ResponseCode::HTTP_VERSION_NOT_SUPPORTED => header("HTTP/3.0 505 HTTP Version Not Supported", true, ResponseCode::HTTP_VERSION_NOT_SUPPORTED),
            ResponseCode::IM_A_TEAPOT => header("HTTP/3.0 418 I'm a teapot", true, ResponseCode::IM_A_TEAPOT),
            ResponseCode::IM_USED => header("HTTP/3.0 226 I'm used", true, ResponseCode::IM_USED),
            ResponseCode::INSUFFICIENT_STORAGE => header("HTTP/3.0 507 Insufficient Storage", true, ResponseCode::INSUFFICIENT_STORAGE),
            ResponseCode::INTERNAL_SERVER_ERROR => header("HTTP/3.0 500 Internal Server Error", true, ResponseCode::INTERNAL_SERVER_ERROR),
            ResponseCode::INVALID_TOKEN => header("HTTP/3.0 498 Invalid Token", true, ResponseCode::INVALID_TOKEN),
            ResponseCode::LENGTH_REQUIRED => header("HTTP/3.0 411 Length Required", true, ResponseCode::LENGTH_REQUIRED),
            ResponseCode::LOCKED => header("HTTP/3.0 423 Locked", true, ResponseCode::LOCKED),
            ResponseCode::LOOP_DETECTED => header("HTTP/3.0 508 Loop Detected", true, ResponseCode::LOOP_DETECTED),
            ResponseCode::METHOD_FAILURE => header("HTTP/3.0 420 Method Failure", true, ResponseCode::METHOD_FAILURE),
            ResponseCode::METHOD_NOT_ALLLOWED => header("HTTP/3.0 405 Method Not Allowed", true, ResponseCode::METHOD_NOT_ALLLOWED),
            ResponseCode::MISDIRECTED_REQUEST => header("HTTP/3.0 421 Misdirected Request", true, ResponseCode::MISDIRECTED_REQUEST),
            ResponseCode::MOVED_PERMANENTLY => header("HTTP/3.0 301 Moved Permanently", true, ResponseCode::MOVED_PERMANENTLY),
            ResponseCode::MULTI_STATUS => header("HTTP/3.0 207 Multi Status", true, ResponseCode::MULTI_STATUS),
            ResponseCode::MULTIPLE_CHOICES => header("HTTP/3.0 300 Multiple Choices", true, ResponseCode::MULTIPLE_CHOICES),
            ResponseCode::NETWORK_AUTHENTICATION_REQUIRED => header("HTTP/3.0 511 Network Authentication Required", true, ResponseCode::NETWORK_AUTHENTICATION_REQUIRED),
            ResponseCode::NO_CONTENT => header("HTTP/3.0 204 No Content", true, ResponseCode::NO_CONTENT),
            ResponseCode::NON_AUTH => header("HTTP/3.0 203 Non-Authoritative Information", true, ResponseCode::NON_AUTH),
            ResponseCode::NOT_ACCEPTABLE => header("HTTP/3.0 406 Not Acceptable", true, ResponseCode::NOT_ACCEPTABLE),
            ResponseCode::NOT_EXTENDED => header("HTTP/3.0 510 Not Extended", true, ResponseCode::NOT_EXTENDED),
            ResponseCode::NOT_FOUND => header("HTTP/3.0 404 Not Found", true, ResponseCode::NOT_FOUND),
            ResponseCode::NOT_IMPLEMENTED => header("HTTP/3.0 501 Not Implemented", true, ResponseCode::NOT_IMPLEMENTED),
            ResponseCode::NOT_MODIFIED => header("HTTP/3.0 304 Not Modified", true, ResponseCode::NOT_MODIFIED),
            ResponseCode::OK => header("HTTP/3.0 200 OK", true, ResponseCode::OK),
            ResponseCode::PAGE_EXPIRED => header("HTTP/3.0 419 Page Expired", true, ResponseCode::PAGE_EXPIRED),
            ResponseCode::PARTIAL_CONTENT => header("HTTP/3.0 206 Partial Content", true, ResponseCode::PARTIAL_CONTENT),
            ResponseCode::PAYLOAD_TOO_LARGE => header("HTTP/3.0 413 Payload Too Large", true, ResponseCode::PAYLOAD_TOO_LARGE),
            ResponseCode::PAYMENT_REQUIRED => header("HTTP/3.0 402 Payment Required", true, ResponseCode::PAYMENT_REQUIRED),
            ResponseCode::PERMANENT_REDIRECT => header("HTTP/3.0 308 Permanent Redirect", true, ResponseCode::PAYMENT_REQUIRED),
            ResponseCode::PRECONDITION_FAILED => header("HTTP/3.0 412 Precondition Failed", true, ResponseCode::PRECONDITION_FAILED),
            ResponseCode::PRECONDITION_REQUIRED => header("HTTP/3.0 428 Precondition Required", true, ResponseCode::PRECONDITION_REQUIRED),
            ResponseCode::PROCESSING => header("HTTP/3.0 102 Processing", true, ResponseCode::PROCESSING),
            ResponseCode::PROXY_AUTHENTICATION_REQUIRED => header("HTTP/3.0 407 Proxy Authentication Required", true, ResponseCode::PROXY_AUTHENTICATION_REQUIRED),
            ResponseCode::RANGE_NOT_SATISFIABLE => header("HTTP/3.0 416 Range Not Satisfiable", true, ResponseCode::RANGE_NOT_SATISFIABLE),
            ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE => header("HTTP/3.0 431 Request Header Fields Too Large", true, ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE),
            ResponseCode::REQUEST_TIMEOUT => header("HTTP/3.0 408 Request Timeout", true, ResponseCode::REQUEST_TIMEOUT),
            ResponseCode::RESET_CONTENT => header("HTTP/3.0 205 Reset Content", true, ResponseCode::RESET_CONTENT),
            ResponseCode::SEE_OTHER => header("HTTP/3.0 303 See Other", true, ResponseCode::SEE_OTHER),
            ResponseCode::SERVICE_UNAVAILABLE => header("HTTP/3.0 503 Service Unavailable", true, ResponseCode::SERVICE_UNAVAILABLE),
            ResponseCode::SWITCH_PROXY => header("HTTP/3.0 306 Switching Proxy", true, ResponseCode::SWITCH_PROXY),
            ResponseCode::SWITCHING_PROTOCOLS => header("HTTP/3.0 101 Switching Protocols", true, ResponseCode::SWITCHING_PROTOCOLS),
            ResponseCode::TEMPORARY_REDIRECT => header("HTTP/3.0 307 Temporary Redirect", true, ResponseCode::TEMPORARY_REDIRECT),
            ResponseCode::TOKEN_REQUIRED => header("HTTP/3.0 499 Token Required", true, ResponseCode::TOKEN_REQUIRED),
            ResponseCode::TOO_EARLY => header("HTTP/3.0 425 Too Early", true, ResponseCode::TOO_EARLY),
            ResponseCode::TOO_MANY_REQUESTS => header("HTTP/3.0 429 Too Many Requests", true, ResponseCode::TOO_MANY_REQUESTS),
            ResponseCode::UNAUTHORIZED => header("HTTP/3.0 401 Unauthorized", true, ResponseCode::UNAUTHORIZED),
            ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS => header("HTTP/3.0 451 Unavailable For Legal Reasons", true, ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS),
            ResponseCode::UNPROCESSABLE_ENTITY => header("HTTP/3.0 422 Unprocessable Entity", true, ResponseCode::UNPROCESSABLE_ENTITY),
            ResponseCode::UNSUPPORTED_MEDIA_TYPE => header("HTTP/3.0 415 Unsupported Media Type", true, ResponseCode::UNSUPPORTED_MEDIA_TYPE),
            ResponseCode::UPGRADE_REQUIRED => header("HTTP/3.0 426 Upgrade Required", true, ResponseCode::UPGRADE_REQUIRED),
            ResponseCode::URI_TOO_LONG => header("HTTP/3.0 414 URI Too Long", true, ResponseCode::URI_TOO_LONG),
            ResponseCode::USE_PROXY => header("HTTP/3.0 305 Use Proxy", true, ResponseCode::USE_PROXY),
            ResponseCode::VARIANT_ALSO_NEGOTIATES => header("HTTP/3.0 506 Variant Also Negotiates", true, ResponseCode::VARIANT_ALSO_NEGOTIATES),
            default => throw new RuntimeException("Response code: {$code} not implemented"),
        };
    }
}
