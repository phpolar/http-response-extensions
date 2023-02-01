<?php

declare(strict_types=1);

namespace Phpolar\Extensions\HttpResponse;

use Phpolar\Extensions\HttpResponse\Tests\Stubs\MemoryStreamStub;
use Phpolar\Extensions\HttpResponse\Tests\Stubs\ResponseStub;
use PHPUnit\Framework\TestCase;

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

    /**
     * @testdox Shall set the HTTP response code.
     */
    public function test1()
    {
        ResponseExtension::extend(new ResponseStub(self::RESPONSE_STATUS))
            ->withBody(new MemoryStreamStub())
            ->send();
        $this->assertSame(self::RESPONSE_STATUS, http_response_code());
    }

    /**
     * @testdox Shall set the HTTP headers.
     */
    public function test2()
    {
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
