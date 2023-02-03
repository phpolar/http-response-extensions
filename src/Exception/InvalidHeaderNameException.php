<?php

declare(strict_types=1);

namespace Phpolar\Extensions\HttpResponse\Exception;

use RuntimeException;

/**
 * Represents the scenario when the header value is invalid.
 *
 * @see https://www.php.net/manual/en/function.header.php
 * @see https://owasp.org/www-community/attacks/HTTP_Response_Splitting
 */
final class InvalidHeaderNameException extends RuntimeException
{
    /**
     * @var string
     */
    protected $message = "Invalid header name detected";
}