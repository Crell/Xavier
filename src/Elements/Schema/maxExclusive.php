<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements\Schema;

use Crell\Xavier\Elements\XmlElement;

class maxExclusive extends XmlElement
{
    protected $_allowedAttributes = [
        'value',
    ];
}
