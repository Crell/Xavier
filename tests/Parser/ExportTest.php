<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use PHPUnit\Framework\TestCase;

class ExportTest extends TestCase
{

    public function test_basic_export_works() : void
    {
        $xml = <<<END
<root>
    <name type="full">John Arbuckle</name>
    <publications>
        <publication>Book 1</publication>
        <publication>Book 2</publication>
    </publications>
</root>
END;

        $parser = new Parser('Test\Space');
        $result = $parser->parse($xml);

        $serialized = $result->export();

        $this->assertStringContainsString('<root>', $serialized);
        $this->assertStringContainsString('<publication>Book 1</publication>', $serialized);
        $this->assertStringContainsString('<publication>Book 2</publication>', $serialized);
        $this->assertStringContainsString('<name type="full">John Arbuckle</name>', $serialized);
    }
}
