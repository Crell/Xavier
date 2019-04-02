<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements;

class IllegalAttribute extends \InvalidArgumentException
{
    public static function create(string $attributeName, string $elementName) : self
    {
        $message = sprintf('The attribute %s is not allowed on the element %s', $attributeName, $elementName);
        return new static($message);
    }
}
