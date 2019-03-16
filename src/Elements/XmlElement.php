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
    protected $name = '';

    /**
     * Attributes on this element.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The textual body of the element.
     *
     * @var string
     */
    protected $content = '';

    /**
     * Child elements not otherwise accounted for by a property.
     *
     * @var XmlElement[]
     */
    public $children = [];

    public function __construct($name, array $attributes = [], string $content = '')
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->content = $content;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->attributes);
    }

    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function __toString()
    {
        return $this->content;
    }
}
