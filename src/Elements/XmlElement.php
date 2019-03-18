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
        return $this->_attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
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
