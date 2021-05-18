<?php
declare(strict_types=1);

namespace Crell\Xavier;

use PHPUnit\Framework\TestCase;

class TreeGeneratorTest extends TestCase
{

    public function test_stuff() : void
    {
        $filename = __DIR__ . '/testdata/po.xsd';
        $schema = file_get_contents($filename);

        $b = new TreeGenerator($schema);
    }
}
