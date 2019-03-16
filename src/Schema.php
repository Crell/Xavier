<?php
declare(strict_types=1);

namespace Crell\Xavier;

class Schema
{

    /**
     * @var \SimpleXMLElement
     */
    protected $schema;

    public function __construct(string $filename)
    {

        $parser = xml_parser_create_ns();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

        xml_parse_into_struct($parser, file_get_contents($filename), $values, $tags);

        print_r($tags);
        print_r($values);

        //$this->schema = simplexml_load_file($filename);
    }

    public function complexTypes() : iterable
    {

/*
        $complex = $this->schema->xpath('//xsd:complexType');

        foreach ($complex as $k => $v) {
            print "$k => $v\n";
        }

//        var_dump($complex);
*/
        return [];
    }

}
