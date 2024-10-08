<?php

declare(strict_types=1);

namespace Redmine\Tests\Unit\Client;

use Exception;
use InvalidArgumentException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Redmine\Client\Client;
use Redmine\Client\NativeCurlClient;
use Redmine\Http\HttpClient;
use stdClass;

#[CoversClass(NativeCurlClient::class)]
class NativeCurlClientTest extends TestCase
{
    use PHPMock;

    public const __NAMESPACE__ = 'Redmine\Client';

    public const DEFAULT_CURL_OPTIONS = [
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_PORT => 80,
        CURLOPT_URL => 'http://test.local/path',
        CURLOPT_HTTPHEADER => [
            'Expect: ',
            'X-Redmine-API-Key: access_token',
        ],
        CURLOPT_VERBOSE => 0,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
    ];

    public function testApiKeyShouldBePassToConstructor(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->assertInstanceOf(NativeCurlClient::class, $client);
        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testShouldPassUsernameAndPasswordToConstructor(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'username',
            'password',
        );

        $this->assertInstanceOf(NativeCurlClient::class, $client);
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testGetLastResponseStatusCodeIsInitialNull(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->assertSame(0, $client->getLastResponseStatusCode());
    }

    public function testGetLastResponseStatusCodeTriggersDeprecationWarning(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        // PHPUnit 10 compatible way to test trigger_error().
        set_error_handler(
            function ($errno, $errstr): bool {
                $this->assertSame(
                    '`Redmine\Client\NativeCurlClient::getLastResponseStatusCode()` is deprecated since v2.8.0, use `\Redmine\Api\AbstractApi::getLastResponse()` instead.',
                    $errstr,
                );

                restore_error_handler();
                return true;
            },
            E_USER_DEPRECATED,
        );

        $client->getLastResponseStatusCode();
    }

    public function testGetLastResponseContentTypeIsInitialEmpty(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->assertSame('', $client->getLastResponseContentType());
    }

    public function testGetLastResponseContentTypeTriggersDeprecationWarning(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        // PHPUnit 10 compatible way to test trigger_error().
        set_error_handler(
            function ($errno, $errstr): bool {
                $this->assertSame(
                    '`Redmine\Client\NativeCurlClient::getLastResponseContentType()` is deprecated since v2.8.0, use `\Redmine\Api\AbstractApi::getLastResponse()` instead.',
                    $errstr,
                );

                restore_error_handler();
                return true;
            },
            E_USER_DEPRECATED,
        );

        $client->getLastResponseContentType();
    }

    public function testGetLastResponseBodyIsInitialEmpty(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->assertSame('', $client->getLastResponseBody());
    }

    public function testGetLastResponseBodyTriggersDeprecationWarning(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        // PHPUnit 10 compatible way to test trigger_error().
        set_error_handler(
            function ($errno, $errstr): bool {
                $this->assertSame(
                    '`Redmine\Client\NativeCurlClient::getLastResponseBody()` is deprecated since v2.8.0, use `\Redmine\Api\AbstractApi::getLastResponse()` instead.',
                    $errstr,
                );

                restore_error_handler();
                return true;
            },
            E_USER_DEPRECATED,
        );

        $client->getLastResponseBody();
    }

    public function testStartAndStopImpersonateUser(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PORT => 80,
            CURLOPT_URL => 'http://test.local/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-Switch-User: Sam',
                'X-Redmine-API-Key: access_token',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(3))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(3))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(6))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(3))
            ->willReturnMap([
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
                [$this->anything(), $this->identicalTo($expectedOptions), true],
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
            ])
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(3))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $client->requestGet('/path');
        $client->startImpersonateUser('Sam');
        $client->requestGet('/path');
        $client->stopImpersonateUser();
        $client->requestGet('/path');
    }

    public function testSetSslVersion(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_PORT => 80,
            CURLOPT_URL => 'http://test.local/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-API-Key: access_token',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(3))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(3))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(6))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(3))
            ->willReturnMap([
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
                [$this->anything(), $this->identicalTo($expectedOptions), true],
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
            ])
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(3))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $client->requestGet('/path');
        $client->setCurlOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        $client->requestGet('/path');
        $client->unsetCurlOption(CURLOPT_HTTP_VERSION);
        $client->requestGet('/path');
    }

    public function testSetSslVerifypeer(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PORT => 80,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_URL => 'http://test.local/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-API-Key: access_token',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(3))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(3))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(6))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(3))
            ->willReturnMap([
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
                [$this->anything(), $this->identicalTo($expectedOptions), true],
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
            ])
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(3))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $client->requestGet('/path');
        $client->setCurlOption(CURLOPT_SSL_VERIFYPEER, 1);
        $client->requestGet('/path');
        $client->unsetCurlOption(CURLOPT_SSL_VERIFYPEER);
        $client->requestGet('/path');
    }

    public function testSetSslVerifyhost(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PORT => 80,
            CURLOPT_SSL_VERIFYHOST => 2, // @see http://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html
            CURLOPT_URL => 'http://test.local/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-API-Key: access_token',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(3))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(3))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(6))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(3))
            ->willReturnMap([
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
                [$this->anything(), $this->identicalTo($expectedOptions), true],
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
            ])
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(3))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $client->requestGet('/path');
        $client->setCurlOption(CURLOPT_SSL_VERIFYHOST, 2);
        $client->requestGet('/path');
        $client->unsetCurlOption(CURLOPT_SSL_VERIFYHOST);
        $client->requestGet('/path');
    }

    public function testSetCustomHttpHeaders(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PORT => 80,
            CURLOPT_URL => 'http://test.local/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-API-Key: access_token',
                'FooBar: test case sensitivity',
                'DOUBLE: This will override the previous header',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(3))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(3))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(6))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(3))
            ->willReturnMap([
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
                [$this->anything(), $this->identicalTo($expectedOptions), true],
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
            ])
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(3))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $client->requestGet('/path');
        $client->setCurlOption(CURLOPT_HTTPHEADER, [
            'FooBar: test case sensitivity',
            'double: This will be overridden',
            'DOUBLE: This will override the previous header',
            'invalid-header-will-be-ignored',
        ]);
        $client->requestGet('/path');
        $client->unsetCurlOption(CURLOPT_HTTPHEADER);
        $client->requestGet('/path');
    }

    public function testSetCustomHost(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PORT => 80,
            CURLOPT_URL => 'http://test.local/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-API-Key: access_token',
                'Host: http://custom.example.com',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(3))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(3))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(6))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(3))
            ->willReturnMap([
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
                [$this->anything(), $this->identicalTo($expectedOptions), true],
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
            ])
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(3))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $client->requestGet('/path');
        $client->setCurlOption(CURLOPT_HTTPHEADER, [
            'Host: http://custom.example.com',
        ]);
        $client->requestGet('/path');
        $client->unsetCurlOption(CURLOPT_HTTPHEADER);
        $client->requestGet('/path');
    }

    public function testSetPort(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PORT => 8080,
            CURLOPT_URL => 'http://test.local/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-API-Key: access_token',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(3))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(3))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(6))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(3))
            ->willReturnMap([
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
                [$this->anything(), $this->identicalTo($expectedOptions), true],
                [$this->anything(), $this->identicalTo(self::DEFAULT_CURL_OPTIONS), true],
            ])
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(3))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $client->requestGet('/path');
        $client->setCurlOption(CURLOPT_PORT, 8080);
        $client->requestGet('/path');
        $client->unsetCurlOption(CURLOPT_PORT);
        $client->requestGet('/path');
    }

    public function testCustomPortWillSetFromSchema(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PORT => 443,
            CURLOPT_URL => 'https://test.local/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-API-Key: access_token',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(1))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(1))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(2))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(1))
            ->with(
                $this->anything(),
                $this->identicalTo($expectedOptions),
            )
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(1))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'https://test.local',
            'access_token',
        );

        $client->requestGet('/path');
    }

    public function testCustomPortWillSetFromUrl(): void
    {
        $expectedOptions = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PORT => 3456,
            CURLOPT_URL => 'http://test.local:3456/path',
            CURLOPT_HTTPHEADER => [
                'Expect: ',
                'X-Redmine-API-Key: access_token',
            ],
            CURLOPT_VERBOSE => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(1))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(1))->willReturn('');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(2))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, 200],
            [$curl, CURLINFO_CONTENT_TYPE, 'application/json'],
        ]));

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray->expects($this->exactly(1))
            ->with(
                $this->anything(),
                $this->identicalTo($expectedOptions),
            )
        ;

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(1))->willReturn(0);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local:3456',
            'access_token',
        );

        $client->requestGet('/path');
    }

    /**
     * @dataProvider getRequestReponseData
     */
    #[DataProvider('getRequestReponseData')]
    public function testRequestsReturnsCorrectContent($method, $data, $boolReturn, $statusCode, $contentType, $content): void
    {
        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(1))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(1))->willReturn($content);

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(2))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, $statusCode],
            [$curl, CURLINFO_CONTENT_TYPE, $contentType],
        ]));

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(1))->willReturn(CURLE_OK);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->assertSame($boolReturn, $client->$method('/path', $data));
        $this->assertSame($statusCode, $client->getLastResponseStatusCode());
        $this->assertSame($contentType, $client->getLastResponseContentType());
        $this->assertSame($content, $client->getLastResponseBody());
    }

    public static function getRequestReponseData(): array
    {
        return [
            ['requestGet', '', true, 101, 'text/plain', ''],
            ['requestGet', '', true, 200, 'application/json', '{"foo_bar": 12345}'],
            ['requestGet', '', true, 301, 'application/json', ''],
            ['requestGet', '', false, 404, 'application/json', '{"title": "404 Not Found"}'],
            ['requestGet', '', false, 500, 'text/plain', 'Internal Server Error'],
            ['requestPost', '{"foo":"bar"}', true, 101, 'text/plain', ''],
            ['requestPost', '{"foo":"bar"}', true, 200, 'application/json', '{"foo_bar": 12345}'],
            ['requestPost', '{"foo":"bar"}', true, 301, 'application/json', ''],
            ['requestPost', '{"foo":"bar"}', false, 404, 'application/json', '{"title": "404 Not Found"}'],
            ['requestPost', '{"foo":"bar"}', false, 500, 'text/plain', 'Internal Server Error'],
            ['requestPut', '{"foo":"bar"}', true, 101, 'text/plain', ''],
            ['requestPut', '{"foo":"bar"}', true, 200, 'application/json', '{"foo_bar": 12345}'],
            ['requestPut', '{"foo":"bar"}', true, 301, 'application/json', ''],
            ['requestPut', '{"foo":"bar"}', false, 404, 'application/json', '{"title": "404 Not Found"}'],
            ['requestPut', '{"foo":"bar"}', false, 500, 'text/plain', 'Internal Server Error'],
            ['requestDelete', '', true, 101, 'text/plain', ''],
            ['requestDelete', '', true, 200, 'application/json', '{"foo_bar": 12345}'],
            ['requestDelete', '', true, 301, 'application/json', ''],
            ['requestDelete', '', false, 404, 'application/json', '{"title": "404 Not Found"}'],
            ['requestDelete', '', false, 500, 'text/plain', 'Internal Server Error'],
        ];
    }

    public function testHandlingOfResponseWithoutContent(): void
    {
        $content = '';
        $statusCode = 204;
        $contentType = null;

        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(1))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(1))->willReturn('');

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');

        $curlGetinfo = $this->getFunctionMock(self::__NAMESPACE__, 'curl_getinfo');
        $curlGetinfo->expects($this->exactly(2))->willReturnMap(([
            [$curl, CURLINFO_HTTP_CODE, $statusCode],
            [$curl, CURLINFO_CONTENT_TYPE, $contentType],
        ]));

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(1))->willReturn(CURLE_OK);

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->assertSame(true, $client->requestPut('/path', '{"foo":"bar"}'));
        $this->assertSame($statusCode, $client->getLastResponseStatusCode());
        $this->assertSame('', $client->getLastResponseContentType());
        $this->assertSame($content, $client->getLastResponseBody());
    }

    public function testCurlErrorThrowsException(): void
    {
        $curl = $this->createMock(stdClass::class);

        $curlInit = $this->getFunctionMock(self::__NAMESPACE__, 'curl_init');
        $curlInit->expects($this->exactly(1))->willReturn($curl);

        $curlExec = $this->getFunctionMock(self::__NAMESPACE__, 'curl_exec');
        $curlExec->expects($this->exactly(1))->willReturn(false);

        $curlSetoptArray = $this->getFunctionMock(self::__NAMESPACE__, 'curl_setopt_array');

        $curlErrno = $this->getFunctionMock(self::__NAMESPACE__, 'curl_errno');
        $curlErrno->expects($this->exactly(1))->willReturn(CURLE_URL_MALFORMAT);

        $curlError = $this->getFunctionMock(self::__NAMESPACE__, 'curl_error');
        $curlError->expects($this->exactly(1))->willReturn('cURL error 3: <url> malformed');

        $curlClose = $this->getFunctionMock(self::__NAMESPACE__, 'curl_close');

        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('cURL error 3: <url> malformed');

        $client->requestGet('/path');
    }

    /**
     * @dataProvider getApiClassesProvider
     */
    #[DataProvider('getApiClassesProvider')]
    public function testGetApiShouldReturnApiInstance(string $apiName, string $class): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->assertInstanceOf($class, $client->getApi($apiName));
    }

    public static function getApiClassesProvider(): array
    {
        return [
            ['attachment', 'Redmine\Api\Attachment'],
            ['group', 'Redmine\Api\Group'],
            ['custom_fields', 'Redmine\Api\CustomField'],
            ['issue', 'Redmine\Api\Issue'],
            ['issue_category', 'Redmine\Api\IssueCategory'],
            ['issue_priority', 'Redmine\Api\IssuePriority'],
            ['issue_relation', 'Redmine\Api\IssueRelation'],
            ['issue_status', 'Redmine\Api\IssueStatus'],
            ['membership', 'Redmine\Api\Membership'],
            ['news', 'Redmine\Api\News'],
            ['project', 'Redmine\Api\Project'],
            ['query', 'Redmine\Api\Query'],
            ['role', 'Redmine\Api\Role'],
            ['time_entry', 'Redmine\Api\TimeEntry'],
            ['time_entry_activity', 'Redmine\Api\TimeEntryActivity'],
            ['tracker', 'Redmine\Api\Tracker'],
            ['user', 'Redmine\Api\User'],
            ['version', 'Redmine\Api\Version'],
            ['wiki', 'Redmine\Api\Wiki'],
        ];
    }

    public function testGetApiShouldThrowException(): void
    {
        $client = new NativeCurlClient(
            'http://test.local',
            'access_token',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`do_not_exist` is not a valid api. Possible apis are `attachment`, `group`, `custom_fields`, `issue`, `issue_category`, `issue_priority`, `issue_relation`, `issue_status`, `membership`, `news`, `project`, `query`, `role`, `search`, `time_entry`, `time_entry_activity`, `tracker`, `user`, `version`, `wiki`');

        $client->getApi('do_not_exist');
    }
}
