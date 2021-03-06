<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Elements\XmlElement;
use Crell\Xavier\NoNamespaceMapDefined;
use Crell\Xavier\NoElementClassFound;
use Crell\Xavier\NoPropertyFound;
use Crell\Xavier\UnknownNamespaceInFile;

class Parser
{
    /**
     * Mapping of XML namespaces (full URLs) to PHP class namespaces.
     */
    protected array $namespaces = [];

    /**
     * The PHP namespace to use for any non-namespaced XML elements.
     *
     * @var string
     */
    protected $globalNamespace = '';

    /**
     * Parser constructor.
     *
     * @param string $phpNs
     *   The namespace of PHP classes that should be used for the "global" namespace
     *   within the XML document.
     * @param bool $strict
     *   If true, missing class or property definitions will result in an exception.
     *   If false, a generic fallback class will be used instead.
     */
    public function __construct(string $phpNs, protected bool $strict = false)
    {
        $this->globalNamespace = $phpNs;
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
    public function addNamespace(string $xmlNs, string $phpNs): static
    {
        $this->namespaces[$xmlNs] = $phpNs;
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
    public function parse(string $xml): XmlElement
    {
        $tags = $this->parseTags($xml);

        // The first element gets special handling, because fence-posting. This makes the code below considerably
        // simpler as it has fewer edge cases to deal with, and we also then always know what the element to return is.
        $tag = array_shift($tags);

        $namespaces = $this->getDeclaredNamespaces($tag);

        $className = $this->mapTagToClass($tag['name'], $tag['namespace'], $namespaces);

        $attributes = array_filter($tag['attributes'], [$this, 'isNotNamespaceDefinition'], \ARRAY_FILTER_USE_KEY);

        $namespace = $namespaces[$tag['namespace']] ?? '';
        $rootElement = new $className($tag['name'], $attributes, $tag['value'], $namespace, array_flip($namespaces));
        $parentStack = [$rootElement];

        foreach ($tags as $tag) {
            $index = count($parentStack);
            if (in_array($tag['type'], ['open', 'complete'])) {
                // Build new Element.
                $className = $this->mapTagToClass($tag['name'], $tag['namespace'], $namespaces);
                $namespace = $namespaces[$tag['namespace']] ?? '';
                $element = new $className($tag['name'], $tag['attributes'], $tag['value'], $namespace);

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
                $parent = $parentStack[$index - 1];
                $attribute = $tag['name'];
                if (isset($parent->$attribute) && is_array($parent->$attribute)) {
                    $parent->$attribute[] = $element;
                }
                elseif (isset($parent->$attribute) && $parent->$attribute instanceof XmlElement) {
                    // Upconvert an element to an array if a second of the same name is found.
                    $parent->$attribute = [$parent->$attribute, $element];
                }
                else {
                    $parent->$attribute = $element;
                }

                // If the element is going to have children, push it onto the stack so the following elements are added
                // as its children.
                if ($tag['type'] === 'open') {
                    $parentStack[] = $element;
                }
            }
            elseif ($tag['type'] === 'close') {
                // We're done with a child-carrying element, so pop it off the stack.
                array_pop($parentStack);
            }
        }
        return $rootElement;
    }

    /**
     * Extracts the namespace definitions from a tag.
     *
     * @param array $tag
     *   The tag definition to process.
     * @return array
     *   An associative array of namespace short-names to namespace URIs.
     */
    protected function getDeclaredNamespaces(array $tag): array
    {
        $namespaces = [];

        $nsDefs = array_filter($tag['attributes'], [$this, 'isNamespaceDefinition'], \ARRAY_FILTER_USE_KEY);

        // We're using array_walk() as a cheap array_map_with_key_support().
        array_walk($nsDefs, function($value, $key) use (&$namespaces) {
            $nsName = substr($key, strlen('xmlns:'));
            $namespaces[$nsName] = $value;
        });

        return $namespaces;
    }

    /**
     * Determines if the specified attribute name string is defining an XML namespace.
     *
     * @param string $key
     *   The XML attribute name.
     * @return bool
     *   True if it's a namespace declaration, false otherwise.
     */
    protected function isNamespaceDefinition(string $key): bool
    {
        return \str_contains($key, 'xmlns:');
    }

    protected function isNotNamespaceDefinition(string $key): bool
    {
        return !\str_contains($key, 'xmlns:');
    }

    /**
     * Maps an XML tag to the corresponding PHP class.
     *
     * @param string $tagName
     *   The name of the tag, without a namespace prefix.
     * @param string $tagNamespace
     *   The namespace prefix, if any.
     * @param array $namespaceMap
     *   An map of namespace short names to URIs, as produced by getDeclaredNamespaces().
     * @return string
     *   The FQCN of the PHP class this tag maps to.
     */
    protected function mapTagToClass(string $tagName, string $tagNamespace, array $namespaceMap): string
    {
        // Map the tag namespace to a PHP namespace.
        if ($tagNamespace) {
            if (!isset($namespaceMap[$tagNamespace])) {
                throw UnknownNamespaceInFile::create($tagNamespace);
            }

            if (!isset($this->namespaces[$namespaceMap[$tagNamespace]])) {
                throw NoNamespaceMapDefined::create($namespaceMap[$tagNamespace]);
            }
            $phpNs = $this->namespaces[$namespaceMap[$tagNamespace]];
        }
        else {
            $phpNs = $this->globalNamespace;
        }

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

    /**
     * Parses an XML string into a nested array of tag definitions.
     *
     * @see https://www.php.net/manual/en/function.xml-parse-into-struct.php
     *
     * @param string $xml
     *   A well-formed XML string to parse.
     * @return array
     *   A nested array of tag definitions.  The format is the same as created by
     *   xml_parse_into_struct(), but with defaults added and a few derived properties.
     */
    protected function parseTags(string $xml): array
    {
        $tags = [];
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);

        // Ensure all properties are always defined so that we don't have to constantly check for missing values later.
        array_walk($tags, function(&$tag) {
            if (!\str_contains($tag['tag'], ':')) {
                $tagName = $tag['tag'];
                $tagNs = '';
            }
            else {
                [$tagNs, $tagName] = explode(':', $tag['tag']);
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

    public function parseFile(string $filename): XmlElement
    {
        return $this->parse(file_get_contents($filename));
    }
}
