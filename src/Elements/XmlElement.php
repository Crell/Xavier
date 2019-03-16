<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements;

class XmlElement
{
    public $name;
    public $attributes;
    public $content;
    public $children;

    public function __construct($name, array $attributes = [], string $content = '')
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->content = $content;
    }
}
