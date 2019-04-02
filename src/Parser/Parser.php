<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Elements\XmlElement;
use Crell\Xavier\NoElementClassFound;
use Crell\Xavier\NoPropertyFound;

class Parser
{

    /**
     * True if a missing class or property should throw an exception.
     *
     * False to fallback to the generic class and dynamic properties.
     *
     * @var bool
     */
    protected $strict = false;

    public function __construct(bool $strict = false)
    {
        $this->strict = $strict;
    }

    /**
     *
     *
     * Originally inspired on http://php.net/manual/en/function.xml-parse-into-struct.php#66487
     *
     * @param string $xml
     * @return XmlElement
     */
    public function parse(string $xml) : XmlElement
    {
        $tags = $this->parseTags($xml);

        // The first element gets special handling, because fence-posting. This makes the code below considerably
        // simpler as it has fewer edge cases to deal with, and we also then always know what the element to return is.
        $tag = array_shift($tags);
        $className = $this->mapTagToClass($tag['tag']);
        $rootElement = new $className($tag['tag'], $tag['attributes'], $tag['value']);

        $parentStack = [$rootElement];
        foreach ($tags as $tag) {
            $index = count($parentStack);
            if (in_array($tag['type'], ['open', 'complete'])) {
                // Build new Element.
                $className = $this->mapTagToClass($tag['tag']);
                $element = new $className($tag['tag'], $tag['attributes'], $tag['value']);

                // In strict mode, use reflection to ensure that the parent element has
                // a properly named property.
                if ($this->strict) {
                    try {
                        $reflect = new \ReflectionObject($parentStack[$index - 1]);
                        // This will throw a ReflectionException if the property does not exist.
                        $reflect->getProperty($tag['tag']);
                    }
                    catch (\ReflectionException $e) {
                        throw NoPropertyFound::create($reflect->name, $tag['tag']);
                    }
                }

                // Assign this element to a property of the parent element, based on its name.
                $parentStack[$index - 1]->{$tag['tag']} = $element;

                // If the element is going to have children, push it onto the stack so the following elements are added
                // as its children.
                if ($tag['type'] == 'open') {
                    $parentStack[] = $element;
                }
            }
            elseif ($tag['type'] == 'close') {
                // We're done with a child-carrying element, so pop it off the stack.
                array_pop($parentStack);
            }
        }
        return $rootElement;
    }

    protected function mapTagToClass(string $tag) : string
    {
        // If we fall back as far as the default in strict mode, it means there was a missing class element definition.
        if ($this->strict) {
            throw NoElementClassFound::create($tag);
        }

        return XmlElement::class;
    }

    protected function parseTags(string $xml) : array
    {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);

        // Ensure all properties are always defined so that we don't have to constantly check for missing values later.
        array_walk($tags, function(&$tag) {
            $tag += [
                'attributes' => [],
                'value' => '',
            ];
        });

        return $tags;
    }

    public function parseFile(string $filename)
    {
        return $this->parse(file_get_contents($filename));
    }
}
