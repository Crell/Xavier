<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Elements\XmlElement;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{

    public function test_parser() : void
    {
        $filename = __DIR__ . '/../testdata/po.xml';
        $p = new Parser();
        $result = $p->parseFile($filename);

        print_r($result);

        $this->assertInstanceOf(XmlElement::class, $result);
        $this->assertEquals('1999-10-20', $result['orderDate']);
        $this->assertEquals('', (string)$result);
    }
}
