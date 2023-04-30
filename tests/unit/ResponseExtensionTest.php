<?php

declare(strict_types=1);

namespace Phpolar\Extensions\HttpResponse;

use Generator;
use Phpolar\Extensions\HttpResponse\Exception\InvalidHeaderNameException;
use Phpolar\Extensions\HttpResponse\Exception\InvalidHeaderValueException;
use Phpolar\HttpCodes\ResponseCode;
use Phpolar\HttpMessageTestUtils\MemoryStreamStub;
use Phpolar\HttpMessageTestUtils\ResponseStub;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use SebastianBergmann\CodeCoverage\Driver\Xdebug3NotEnabledException;

/**
 * @covers \Phpolar\Extensions\HttpResponse\ResponseExtension
 * @runTestsInSeparateProcesses
 */
final class ResponseExtensionTest extends TestCase
{
    const RESPONSE_CONTENT = "it worked!";
    const RESPONSE_STATUS = 500;
    const HEADER_KEY = "Content-Range";
    const HEADER_VALUE = "bytes 21010-47021/47022";

    protected function statusCodes(): Generator
    {
        $reflection = new ReflectionClass(ResponseCode::class);
        $codes = $reflection->getConstants();
        $protocolVersions = [
            "",
            "1.0",
            "1.1",
            "2.0",
            "3.0"
        ];
        foreach ($protocolVersions as $protocolVersion) {
            foreach (
                array_diff(
                    $codes,
                    [
                        ResponseCode::SHOPIFY_REQUEST_HEADER_FIELDS_TOO_LARGE,
                        ResponseCode::THIS_IS_FINE,
                    ]
                ) as $code
            ) {
                yield [$protocolVersion, $code];
            }
        }
    }

    protected function notImplementedStatusCodes(): Generator
    {
        $protocolVersions = [
            "",
            "1.0",
            "1.1",
            "2.0",
            "3.0"
        ];
        foreach ($protocolVersions as $protocolVersion) {
            foreach (
                [PHP_INT_MAX] as $code
            ) {
                yield [$protocolVersion, $code];
            }
        }
    }

    /**
     * @testdox Shall set the HTTP response code.
     * @dataProvider statusCodes()
     */
    public function test1(string $protocolVersion, int $statusCode)
    {
        ResponseExtension::extend(
            (new ResponseStub($statusCode))->withProtocolVersion($protocolVersion)
        )->withBody(new MemoryStreamStub())
            ->send();
        $this->assertSame($statusCode, http_response_code());
    }

    /**
     * @testdox Shall set the HTTP response code.
     * @dataProvider notImplementedStatusCodes()
     */
    public function test1b(string $protocolVersion, int $statusCode)
    {
        $this->expectException(RuntimeException::class);
        ResponseExtension::extend(
            (new ResponseStub($statusCode))->withProtocolVersion($protocolVersion)
        )->withBody(new MemoryStreamStub())
            ->send();
    }

    /**
     * @testdox Shall set the HTTP headers.
     */
    public function test2()
    {

        if (in_array("xdebug", get_loaded_extensions()) === false) {
            $this->markTestSkipped("This test requires XDebug to be enabled.");
        }
        ResponseExtension::extend(new ResponseStub())
            ->withBody(new MemoryStreamStub())
            ->withHeader(self::HEADER_KEY, self::HEADER_VALUE)
            ->send();

        $this->assertContains(
            sprintf("%s: %s", self::HEADER_KEY, self::HEADER_VALUE),
            \xdebug_get_headers()
        );
    }

    /**
     * @testdox Shall throw exception if HTTP header name is invalid.
     *
     * @see https://www.php.net/manual/en/function.header.php
     * @see https://owasp.org/www-community/attacks/HTTP_Response_Splitting
     * @see https://www.cs.montana.edu/courses/csci476/topics/http_response_splitting.pdf
     */
    public function test2b()
    {
        $this->expectException(InvalidHeaderNameException::class);
        ResponseExtension::extend(new ResponseStub())
            ->withBody(new MemoryStreamStub())
            ->withHeader(self::HEADER_KEY . PHP_EOL, self::HEADER_VALUE)
            ->send();
    }

    /**
     * @testdox Shall throw exception if HTTP header value is invalid.
     *
     * @see https://www.php.net/manual/en/function.header.php
     * @see https://owasp.org/www-community/attacks/HTTP_Response_Splitting
     * @see https://www.cs.montana.edu/courses/csci476/topics/http_response_splitting.pdf
     */
    public function test2c()
    {
        $this->expectException(InvalidHeaderValueException::class);
        ResponseExtension::extend(new ResponseStub())
            ->withBody(new MemoryStreamStub())
            ->withHeader(self::HEADER_KEY, self::HEADER_VALUE . PHP_EOL)
            ->send();
    }

    /**
     * @testdox Shall send the response body.
     */
    public function test3()
    {
        $this->expectOutputString(self::RESPONSE_CONTENT);
        ResponseExtension::extend(new ResponseStub())
            ->withBody(new MemoryStreamStub(self::RESPONSE_CONTENT))
            ->send();
    }
}
