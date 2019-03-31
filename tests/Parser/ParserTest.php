<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Classifier\ClassBuilder;
use Crell\Xavier\Classifier\PropertyDefinition;
use Crell\Xavier\Elements\XmlElement;
use Crell\Xavier\NoElementClassFound;
use Crell\Xavier\NoPropertyFound;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ParserTest extends TestCase
{

    public function test_parser() : void
    {
        $ns = 'Test\Space';
        // This is a very incomplete list.
        $map['purchaseOrder'] = $this->declareElement('purchaseOrder', $ns);
        $map['billTo'] = $this->declareElement('billTo', $ns);
        $map['shipTo'] = $this->declareElement('shipTo', $ns);
        $map['comment'] = $this->declareElement('comment', $ns);

        $p = $this->makeParser($map);

        $filename = __DIR__ . '/../testdata/po.xml';
        $result = $p->parseFile($filename);

        $this->assertInstanceOf($map['purchaseOrder'], $result);
        $this->assertEquals('1999-10-20', $result['orderDate']);
        $this->assertEquals('', (string)$result);

        // The defined elements get mapped to their class.
        $this->assertInstanceOf($map['shipTo'], $result->shipTo);
        $this->assertInstanceOf($map['billTo'], $result->billTo);
        $this->assertInstanceOf($map['comment'], $result->comment);
        $this->assertEquals('Hurry, my lawn is going wild', $result->comment);

        // Undefined elements get mapped to the parent XmlElement class.
        $this->assertInstanceOf(XmlElement::class, $result->shipTo->name);
        $this->assertEquals('Alice Smith', $result->shipTo->name);
    }

    public function test_strict_mode_rejects_missing_class_definitions() : void
    {
        $this->expectException(NoElementClassFound::class);

        $ns = 'Test\Space';
        // This is a very incomplete list.
        $map['purchaseOrder'] = $this->declareElement('purchaseOrder', $ns, ['shipTo', 'billTo']);
        $map['billTo'] = $this->declareElement('billTo', $ns);
        $map['shipTo'] = $this->declareElement('shipTo', $ns);

        $p = $this->makeParser($map, true);

        $filename = __DIR__ . '/../testdata/po.xml';
        $result = $p->parseFile($filename);
    }

    public function test_strict_mode_rejects_missing_property_definitions() : void
    {
        $this->expectException(NoPropertyFound::class);

        $ns = 'Test\Space';
        // This is a very incomplete list.
        $map['purchaseOrder'] = $this->declareElement('purchaseOrder', $ns);
        $map['comment'] = $this->declareElement('comment', $ns);

        $p = $this->makeParser($map, true);

        $xml = <<<XML
  <purchaseOrder orderDate="1999-10-20">
    <comment>Hurry, my lawn is going wild</comment>
</purchaseOrder>
XML;

        $result = $p->parse($xml);
    }

    public function test_strict_mode_accepts_fully_defined_classes() : void
    {
        $ns = 'Test\Space';
        // This is a very incomplete list.
        $map['purchaseOrder'] = $this->declareElement('purchaseOrder', $ns, ['shipTo', 'billTo']);
        $map['billTo'] = $this->declareElement('billTo', $ns);
        $map['shipTo'] = $this->declareElement('shipTo', $ns);

        $p = $this->makeParser($map, true);

        $xml = <<<END
<?xml version="1.0"?>
<purchaseOrder orderDate="1999-10-20">
    <shipTo country="US">
    </shipTo>
    <billTo country="US">
    </billTo>
</purchaseOrder>
END;

        $result = $p->parse($xml);

        $this->assertInstanceOf($map['purchaseOrder'], $result);
    }

    public function test_xml_with_empty_root_parses_without_error() : void
    {
        $ns = 'Test\Space';
        $map['emptyRoot'] = $this->declareElement('emptyRoot', $ns);

        $p = $this->makeParser($map);

        $xml = "<emptyRoot a=\"foo\" b=\"bar\" />";
        $result = $p->parse($xml);

        $this->assertInstanceOf("{$ns}\\emptyRoot", $result);
        $this->assertEquals('foo', $result['a']);
        $this->assertEquals('bar', $result['b']);
    }

    /**
     * Builds a Parser object for testing.
     *
     * This will probably change in the future when I have a better way to define
     * the schema for a parser other than "extended it and hard code an array."
     *
     * @param array $elementMap
     *   Array of element names to FQCN PHP class names.
     * @param bool $strict
     *   Whether the parser should run in strict mode or not.
     * @return Parser
     *   A parser to test.
     */
    protected function makeParser(array $elementMap, bool $strict = false) : Parser
    {
        $parser = new class($elementMap, $strict) extends Parser {
            protected $elementMap;

            public function __construct(array $elementMap, bool $strict = false)
            {
                parent::__construct($strict);
                $this->elementMap = $elementMap;
            }

            protected function mapTagToClass(string $tag): string
            {
                $map = $this->elementMap;

                return $map[$tag] ?? parent::mapTagToClass($tag);
            }
        };

        return $parser;
    }

    /**
     * Declares a new XmlElement child class into the current process memory.
     *
     * @param string $name
     *   The class/tag name (case sensitive).
     * @param string $namespace
     *   The namespace in which to declare the class.
     * @param array $properties
     *   The public properties this class should have.
     * @return string
     *   The full class name of the just-declared element class.
     */
    protected function declareElement(string $name, string $namespace, array $properties = []) : string
    {
        $b = new ClassBuilder($name, $namespace, XmlElement::class);

        foreach ($properties as $prop) {
            $b->addProperty(new PropertyDefinition($prop));
        }

        $b->declare();

        return $b->fqcn();
    }
}
