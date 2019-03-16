<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Elements\XmlElement;

class Parser
{

    public function __construct()
    {
    }

    /**
     *
     *
     * Originally based on http://php.net/manual/en/function.xml-parse-into-struct.php#66487
     *
     * @param string $xml
     * @return XmlElement
     */
    public function parse(string $xml) : XmlElement
    {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);

        $elements = [];  // the currently filling [child] XmlElement array
        $stack = [];
        foreach ($tags as $tag) {
            $tag += [
                'attributes' => [],
                'value' => '',
            ];
            $index = count($elements);
            if ($tag['type'] == "complete" || $tag['type'] == "open") {
                // Build new Element.
                $className = $this->mapTagToClass($tag['tag']);
                $elements[$index] = new $className($tag['tag'], $tag['attributes'], $tag['value']);

                // Initialize the element's children array.
                // Then push onto the stack so that the next tag processed
                // becomes a child of this element.
                if ($tag['type'] == "open") {  // push
                    $elements[$index]->children = [];
                    $stack[count($stack)] = &$elements;
                    $elements = &$elements[$index]->children;
                }
            }
            // On a closing tag, pop the working parent off the stack.
            else if ($tag['type'] == "close") {
                $elements = &$stack[count($stack) - 1];
                unset($stack[count($stack) - 1]);
            }
        }
        return $elements[0];  // the single top-level element
    }

    protected function mapTagToClass(string $tag) : string
    {
        return XmlElement::class;
    }


    public function parseFile(string $filename)
    {
        return $this->parse(file_get_contents($filename));
    }
}
