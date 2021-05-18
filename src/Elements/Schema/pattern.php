<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements\Schema;

use Crell\Xavier\Elements\XmlElement;

class pattern extends XmlElement
{
    protected array $_allowedAttributes = [
        'value',
    ];
}
