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
        $tags = $this->parseTags($xml);

        $elements = [];  // the currently filling [child] XmlElement array
        $stack = [];

        $parentStack = [];
        foreach ($tags as $tag) {
            $tag += [
                'attributes' => [],
                'value' => '',
            ];
            $index = count($parentStack);
            switch ($tag['type']) {
                case 'open':
                    // Build new Element.
                    $className = $this->mapTagToClass($tag['tag']);
                    $element = new $className($tag['tag'], $tag['attributes'], $tag['value']);
                    if ($index != 0) {
                        $parentStack[$index - 1]->{$tag['tag']} = $element;
                    }
                    $parentStack[] = $element;
                    break;
                case 'complete':
                    // Build new Element.
                    $className = $this->mapTagToClass($tag['tag']);
                    $element = new $className($tag['tag'], $tag['attributes'], $tag['value']);
                    if ($index != 0) {
                        $parentStack[$index - 1]->{$tag['tag']} = $element;
                    }
                    break;
                case 'close':
                    $ret = array_pop($parentStack);
                    break;
            }
            /*
            if ($tag['type'] == "complete" || $tag['type'] == "open") {

                if ($index == 0) {
                    $parentStack[] = $element;
                }
                else {
                }

                // If this element has children, push it onto the stack so that the next element
                // processed is registered as a child of it.
                if ($tag['type'] == "open") {
//                    $stack[count($stack)] = &$elements;
//                    $elements = &$elements[$index]->children;
                }
            }
            // On a closing tag, pop the working parent off the stack.
            else if ($tag['type'] == "close") {
//                $elements = &$stack[count($stack) - 1];
//                unset($stack[count($stack) - 1]);
            }
            */
        }
        return $ret;
//        return $elements[0];  // the single top-level element
    }

    protected function mapTagToClass(string $tag) : string
    {
        return XmlElement::class;
    }

    protected function parseTags(string $xml) : array
    {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);

        return $tags;
    }

    public function parseFile(string $filename)
    {
        return $this->parse(file_get_contents($filename));
    }
}
