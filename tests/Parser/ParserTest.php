<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Elements\XmlElement;
use Crell\Xavier\Parser\Elements\billTo;
use Crell\Xavier\Parser\Elements\comment;
use Crell\Xavier\Parser\Elements\purchaseOrder;
use Crell\Xavier\Parser\Elements\shipTo;
use PHPUnit\Framework\TestCase;

class MockParser extends Parser
{
    protected function mapTagToClass(string $tag): string
    {
        $map = [
            'purchaseOrder' => purchaseOrder::class,
            'shipTo' => shipTo::class,
            'billTo' => billTo::class,
            'comment' => comment::class,
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

        //print_r($result);

        $this->assertInstanceOf(purchaseOrder::class, $result);
        $this->assertEquals('1999-10-20', $result['orderDate']);
        $this->assertEquals('', (string)$result);

        $this->assertInstanceOf(shipTo::class, $result->shipTo);
        $this->assertInstanceOf(billTo::class, $result->billTo);
        $this->assertInstanceOf(comment::class, $result->comment);
        $this->assertEquals('Hurry, my lawn is going wild', $result->comment);

        $this->assertInstanceOf(XmlElement::class, $result->shipTo->name);
        $this->assertEquals('Alice Smith', $result->shipTo->name);

    }
}
