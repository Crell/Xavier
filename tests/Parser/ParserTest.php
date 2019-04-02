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

        $p = new Parser($ns);

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

        $p = new Parser($ns, true);

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

        $p = new Parser($ns,true);

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

        $p = new Parser($ns, true);

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

        $p = new Parser($ns);

        $xml = "<emptyRoot a=\"foo\" b=\"bar\" />";
        $result = $p->parse($xml);

        $this->assertInstanceOf("{$ns}\\emptyRoot", $result);
        $this->assertEquals('foo', $result['a']);
        $this->assertEquals('bar', $result['b']);
    }

    public function test_xml_with_namespaces_parses_to_objects() : void
    {
        $xml = <<<END
<myns:thing xmlns:myns="http://example.com/namespace">
    <myns:stuff>
    Stuff goes here.
</myns:stuff>
</thing>
END;

        $phpNs = 'Test\Space';
        $map['thing'] = $this->declareElement('thing', $phpNs, ['stuff']);
        $map['stuff'] = $this->declareElement('stuff', $phpNs);

        $p = new Parser($phpNs);
        $p->addNamespace('http://example.com/namespace', 'Test\Space');

        $result = $p->parse($xml);

        $this->assertInstanceOf("$phpNs\\thing", $result);
        $this->assertInstanceOf("$phpNs\\stuff", $result->stuff);
    }

    public function test_xml_with_multiple_namespaces_parses_to_objects() : void
    {
        $xml = <<<END
<myns:thing xmlns:myns="http://example.com/namespace" xmlns:yourns="http://example.com/other">
    <myns:stuff>
        <yourns:beep>
            Stuff goes here.
        </yourns:beep>
        <yourns:stuff>
            Someone else's stuff goes here.
        </yourns:stuff>
</myns:stuff>
</thing>
END;

        $myNs = 'My\Ns';
        $yourNs = 'Your\Ns';
        $this->declareElement('thing', $myNs, ['stuff']);
        $this->declareElement('stuff', $myNs, ['beep', 'stuff']);
        $this->declareElement('beep', $yourNs);
        $this->declareElement('stuff', $yourNs);

        $p = new Parser('');
        $p->addNamespace('http://example.com/namespace', $myNs);
        $p->addNamespace('http://example.com/other', $yourNs);

        $result = $p->parse($xml, true);

        $this->assertInstanceOf("$myNs\\thing", $result);
        $this->assertInstanceOf("$myNs\\stuff", $result->stuff);
        $this->assertInstanceOf("$yourNs\\beep", $result->stuff->beep);
        $this->assertInstanceOf("$yourNs\\stuff", $result->stuff->stuff);
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
