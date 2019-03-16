<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{

    public function test_parser() : void
    {
        $filename = __DIR__ . '/../testdata/po.xml';
        $p = new Parser();
        $result = $p->parseFile($filename);

        print_r($result);
    }
}
