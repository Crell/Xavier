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

    /**
     * Mapping of XML namespaces (full URLs) to PHP class namespaces.
     *
     * @var array
     */
    protected $namespaces = [];

    /**
     * The PHP namespace to use for any non-namespaced XML elements.
     *
     * @var string
     */
    protected $globalNamespace = '';

    public function __construct(bool $strict = false)
    {
        $this->strict = $strict;
    }

    /**
     * Associates an XML namespace with a PHP namespace.
     *
     * Any elements found in the XML namespace will be mapped to classes
     * in the PHP namespace.
     *
     * @param string $xmlNs
     *   An XML namespace.  This is a full URL, not just the abbreviation used in the file.
     * @param string $phpNs
     *   A PHP namespace.
     * @return Parser
     *   The called object.
     */
    public function addNamespace(string $xmlNs, string $phpNs) : self
    {
        $this->namespaces[$xmlNs] = $phpNs;

        return $this;
    }

    public function setGlobalNamespace(string $phpNs) : self
    {
        $this->globalNamespace = $phpNs;
        return $this;
    }

    /**
     * Parse an XML string into a tree of defined objects.
     *
     * Originally inspired on http://php.net/manual/en/function.xml-parse-into-struct.php#66487
     *
     * @param string $xml
     *   A well-formed XML string
     * @return XmlElement
     *   The root element's corresponding object. It practice it will usually be
     *   a generated subclass of XmlElement.
     */
    public function parse(string $xml) : XmlElement
    {
        $tags = $this->parseTags($xml);

        // The first element gets special handling, because fence-posting. This makes the code below considerably
        // simpler as it has fewer edge cases to deal with, and we also then always know what the element to return is.
        $tag = array_shift($tags);

        $isNamespaceDefinition = function($key) {
            return strpos($key, 'xmlns:') !== false;
        };

        $nsDefs = array_filter($tag['attributes'], $isNamespaceDefinition, \ARRAY_FILTER_USE_KEY);

        $namespaces = [];

        array_walk($nsDefs, function($value, $key) use (&$namespaces) {
            $nsName = substr($key, strlen('xmlns:'));
            $namespaces[$nsName] = $value;
        });


        $className = $this->mapTagToClass($tag['name'], $tag['namespace'], $namespaces);
        $rootElement = new $className($tag['name'], $tag['attributes'], $tag['value']);

        $parentStack = [$rootElement];
        foreach ($tags as $tag) {
            $index = count($parentStack);
            if (in_array($tag['type'], ['open', 'complete'])) {
                // Build new Element.
                $className = $this->mapTagToClass($tag['name'], $tag['namespace'], $namespaces);
                $element = new $className($tag['name'], $tag['attributes'], $tag['value']);

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
                $parentStack[$index - 1]->{$tag['name']} = $element;

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

    protected function mapTagToClass(string $tagName, string $tagNamespace, array $namespaceMap) : string
    {
        // Map the tag namespace to a PHP namespace.
        $phpNs = $tagNamespace ? $this->namespaces[$namespaceMap[$tagNamespace]] : $this->globalNamespace;

        $className = "{$phpNs}\\{$tagName}";

        if (!class_exists($className)) {
            $className = XmlElement::class;
        }

        // If we fall back as far as the default in strict mode, it means there was a missing class element definition.
        if ($this->strict && $className == XmlElement::class) {
            $originalTagName = trim("$tagNamespace:$tagName", ':');
            throw NoElementClassFound::create($originalTagName);
        }

        return $className;
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
            if (strpos($tag['tag'], ':') === false) {
                $tagName = $tag['tag'];
                $tagNs = '';
            }
            else {
                list($tagNs, $tagName) = explode(':', $tag['tag']);
            }
            $tag += [
                'attributes' => [],
                'value' => '',
                'name' => $tagName,
                'namespace' => $tagNs,
            ];
        });

        return $tags;
    }

    public function parseFile(string $filename)
    {
        return $this->parse(file_get_contents($filename));
    }
}
