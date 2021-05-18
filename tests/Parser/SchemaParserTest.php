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

        static::assertInstanceOf(schema::class, $result);
        static::assertInstanceOf(annotation::class, $result->annotation);
        static::assertIsArray($result->complexType);
        static::assertCount(3, $result->complexType);
        static::assertInstanceOf(complexType::class, $result->complexType[0]);
    }

}
