<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements;

class XmlElement implements \ArrayAccess
{
    /**
     * The tag name of this element.
     *
     * @var string
     */
    protected $_name = '';

    /**
     * Attributes on this element.
     *
     * @var array
     */
    protected $_attributes = [];

    /**
     * @var array
     */
    protected $_allowedAttributes = [];

    /**
     * The textual body of the element.
     *
     * @var string
     */
    protected $_content = '';

    public function __construct($name, array $attributes = [], string $content = '')
    {
        $this->_name = $name;
        $this->_attributes = $attributes;
        $this->_content = $content;
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
     * @return string
     */
    public function export() : string
    {
        $reflO = new \ReflectionObject($this);

        $properties = $reflO->getProperties(\ReflectionProperty::IS_PUBLIC);

        $children = implode(PHP_EOL, array_map([$this, 'exportChild'], $properties));

        $attribs = [];
        foreach ($this->_attributes as $key => $value) {
            $attribs[] = "$key=\"$value\"";
        }

        $attribString = $attribs ? ' ' . implode(' ', $attribs) : '';

        $out = "<{$this->_name}{$attribString}>";

        if ($children) {
            $out .= PHP_EOL . $children;
        }

        if ($this->_content) {
            $out .= $this->_content;
        }

        $out .= "</{$this->_name}>" . PHP_EOL;

        return $out;
    }

    protected function exportChild(\ReflectionProperty $property) : string
    {
        $propName = $property->getName();
        if (is_array($this->$propName)) {
            return implode('', array_map(function(XmlElement $elm) {
                return $elm->export();
            }, $this->$propName));
        }
        return $this->$propName->export();
    }
}
