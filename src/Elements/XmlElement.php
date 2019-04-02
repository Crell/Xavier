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

    /**
     * Child elements not otherwise accounted for by a property.
     *
     * @var XmlElement[]
     */
    public $children = [];

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
}
