<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Elements\XmlElement;
use Crell\Xavier\Parser\Elements\purchaseOrder;
use PHPUnit\Framework\TestCase;

class MockParser extends Parser {
    protected function mapTagToClass(string $tag): string
    {
        $map = [
            'purchaseOrder' => purchaseOrder::class,
        ];

        return $map[$tag] ?? parent::mapTagToClass($tag);
    }
}

class ParserTest extends TestCase
{

    public function test_parser() : void
    {
        $filename = __DIR__ . '/../testdata/po.xml';
        $p = new MockParser();
        $result = $p->parseFile($filename);

        print_r($result);

        $this->assertInstanceOf(purchaseOrder::class, $result);
        $this->assertEquals('1999-10-20', $result['orderDate']);
        $this->assertEquals('', (string)$result);
    }
}
