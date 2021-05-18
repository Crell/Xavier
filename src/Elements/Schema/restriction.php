<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements\Schema;

use Crell\Xavier\Elements\XmlElement;

class restriction extends XmlElement
{
    protected array $_allowedAttributes = [
        'base',
    ];

    public $maxExclusive;

    public $pattern;
}
