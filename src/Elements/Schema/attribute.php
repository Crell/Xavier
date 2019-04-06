<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements\Schema;

use Crell\Xavier\Elements\XmlElement;

class attribute extends XmlElement
{
    protected $_allowedAttributes = [
        'name',
        'type',
        'fixed',
    ];
}
