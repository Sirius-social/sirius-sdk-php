<?php

namespace Siruis\Tests\Messaging;

use PHPUnit\Framework\TestCase;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Messaging\Type\Type;

class TestMessages extends TestCase
{
    /** @test
     * @throws SiriusInvalidType
     */
    public function test_type_parsing()
    {
        $str1 = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test-protocol/1.0/name';
        $type = Type::fromString($str1);
        self::assertEquals('did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/', $type->doc_uri);
        self::assertEquals('test-protocol', $type->protocol);
        self::assertEquals('name', $type->name);
        self::assertEquals('1.0', $type->version);
        self::assertEquals(1, $type->version_info->major);
        self::assertEquals(0, $type->version_info->minor);
        self::assertEquals(0, $type->version_info->patch);

        $str2 = 'https://didcomm.org/test-protocol/1.2/name';
        $type = Type::fromString($str2);
        self::assertEquals('https://didcomm.org/', $type->doc_uri);
        self::assertEquals('test-protocol', $type->protocol);
        self::assertEquals('name', $type->name);
        self::assertEquals('1.2', $type->version);
        self::assertEquals(1, $type->version_info->major);
        self::assertEquals(2, $type->version_info->minor);
        self::assertEquals(0, $type->version_info->patch);
    }
}
