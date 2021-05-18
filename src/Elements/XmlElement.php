<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements;

class XmlElement implements \ArrayAccess
{
    /**
     * Map from XML namespace URLs to short namespaces.
     *
     * This should only ever be populated on the root element.
     * The behavior elsewhere is undefined.
     */
    protected array $_namespaces = [];

    /**
     * The namespace URL of this element.
     */
    protected string $_namespace = '';

    /**
     * The tag name of this element.
     */
    protected string $_name = '';

    /**
     * Attributes on this element.
     */
    protected array $_attributes = [];

    /**
     * Attributes allowed on this element.
     *
     * An element class may provide this list to forcably prevent invalid attributes from being set.
     */
    protected array $_allowedAttributes = [];

    /**
     * The textual body of the element.
     */
    protected string $_content = '';

    /**
     * Constructs a new XlmElement object.
     *
     * These properties are defined manually rather than through constructor promotion
     * so that the constructor arguments can avoid the leading _.  That is necessary
     * to avoid collision in child classes with child elements of the element represented.
     * A small price to pay.
     *
     * @param string $name
     * @param array $attributes
     * @param string $content
     * @param string $namespace
     * @param array $namespaces
     */
    public function __construct(string $name, array $attributes = [], string $content = '', string $namespace = '', array $namespaces = [])
    {
        $this->_name = $name;
        $this->_attributes = $attributes;
        $this->_content = $content;
        $this->_namespace = $namespace;
        $this->_namespaces = $namespaces;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_attributes);
    }

    public function offsetGet($offset)
    {
        if ($this->_allowedAttributes && !isset($this->_allowedAttributes[$offset])) {
            throw IllegalAttribute::create($offset, $this->_name);
        }
        return $this->_attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($this->_allowedAttributes && !isset($this->_allowedAttributes[$offset])) {
            throw IllegalAttribute::create($offset, $this->_name);
        }
        $this->_attributes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_attributes[$offset]);
    }

    public function __toString()
    {
        return $this->_content;
    }

    /**
     * Renders the element tree to a string.
     *
     * @todo Make the formatting of the string prettier.
     *
     * @param array $namespaceMap
     *   A map of namespace URIs to namespace prefixes.
     *   For internal use only. Do not call.
     * @return string
     */
    public function export(array $namespaceMap = []): string
    {
        $namespaceMap = $namespaceMap ? $namespaceMap : $this->_namespaces;
        $prefix = $namespaceMap ? $namespaceMap[$this->_namespace] : '';
        $name = $prefix ? "$prefix:$this->_name" : $this->_name;

        $reflO = new \ReflectionObject($this);
        $properties = $reflO->getProperties(\ReflectionProperty::IS_PUBLIC);

        $children = [];
        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            $children[] = $this->exportChild($property, $namespaceMap);
        }

        $attribs = [];
        foreach ($this->_attributes as $key => $value) {
            $attribs[] = "$key=\"$value\"";
        }

        foreach ($this->_namespaces as $url => $tagNs) {
            $attribs[] = "xmlns:{$tagNs}=\"{$url}\"";
        }

        $attribString = $attribs ? ' ' . implode(' ', $attribs) : '';

        $out = "<{$name}{$attribString}>";

        if ($children) {
            $out .= PHP_EOL . implode('', $children);
        }

        if ($this->_content) {
            $out .= $this->_content;
        }

        $out .= "</{$name}>" . PHP_EOL;

        return $out;
    }

    protected function exportChild(\ReflectionProperty $property, array $namespaceMap): string
    {
        $propName = $property->getName();
        if (is_array($this->$propName)) {
            return implode('', array_map(fn(XmlElement $elm) => $elm->export($namespaceMap), $this->$propName));
        }
        return $this->$propName->export($namespaceMap);
    }
}
