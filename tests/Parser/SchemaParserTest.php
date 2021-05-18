<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Elements\Schema\annotation;
use Crell\Xavier\Elements\Schema\complexType;
use PHPUnit\Framework\TestCase;
use Crell\Xavier\Elements\Schema\schema;

/**
 * @runTestsInSeparateProcesses
 */
class SchemaParserTest extends TestCase
{
    use ElementUtilities;

    public function test_schema() : void
    {
        $p = new SchemaParser();

        $filename = __DIR__ . '/../testdata/po.xsd';
        /** @var schema $result */
        $result = $p->parseFile($filename);

        $this->assertInstanceOf(schema::class, $result);
        $this->assertInstanceOf(annotation::class, $result->annotation);
        $this->assertIsArray($result->complexType);
        $this->assertCount(3, $result->complexType);
        $this->assertInstanceOf(complexType::class, $result->complexType[0]);
    }

}
