<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Classifier\ClassBuilder;
use Crell\Xavier\Classifier\PropertyDefinition;
use Crell\Xavier\Elements\IllegalAttribute;
use Crell\Xavier\Elements\XmlElement;
use Crell\Xavier\NoElementClassFound;
use Crell\Xavier\NoNamespaceMapDefined;
use Crell\Xavier\NoPropertyFound;
use Crell\Xavier\UnknownNamespaceInFile;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ParserTest extends TestCase
{
    use ElementUtilities;

    public function test_parser() : void
    {
        $ns = 'Test\Space';
        // This is a very incomplete list.
        $map['purchaseOrder'] = $this->declareElement('purchaseOrder', $ns, ['billTo', 'shipTo']);
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
        $map['purchaseOrder'] = $this->declareElement('purchaseOrder', $ns, ['shipTo', 'billTo'], ['orderDate']);
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

    public function test_xml_with_missing_namespace_throws() : void
    {
        $this->expectException(UnknownNamespaceInFile::class);

        $xml = <<<END
<myns:thing xmlns:myns="http://example.com/namespace">
    <yourns:stuff>
    Stuff goes here.
    </yourns:stuff>
</thing>
END;

        $phpNs = 'Test\Space';

        $p = new Parser($phpNs);
        $p->addNamespace('http://example.com/namespace', 'Test\Space');

        $result = $p->parse($xml);
    }

    public function test_xml_with_missing_namespace_map_throws() : void
    {
        $this->expectException(NoNamespaceMapDefined::class);

        $xml = <<<END
<myns:thing xmlns:myns="http://example.com/namespace">
    <myns:stuff>
    Stuff goes here.
    </myns:stuff>
</thing>
END;

        $phpNs = 'Test\Space';

        $p = new Parser($phpNs);

        $result = $p->parse($xml);
    }

    public function test_illegal_attribute_is_rejected_on_set() : void
    {
        $this->expectException(IllegalAttribute::class);

        $xml = <<<END
<myns:thing xmlns:myns="http://example.com/namespace">
    <myns:stuff myattrib="bob">
    Stuff goes here.
</myns:stuff>
</thing>
END;

        $phpNs = 'Test\Space';
        $map['thing'] = $this->declareElement('thing', $phpNs, ['stuff'], ['myattrib']);
        $map['stuff'] = $this->declareElement('stuff', $phpNs);

        $this->assertClassHasAttribute('_allowedAttributes', 'Test\Space\thing');

        $p = new Parser($phpNs);
        $p->addNamespace('http://example.com/namespace', 'Test\Space');

        $result = $p->parse($xml);

        $result['fakeattrib'];
    }


}
