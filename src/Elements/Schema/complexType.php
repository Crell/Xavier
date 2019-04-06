<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements\Schema;

use Crell\Xavier\Elements\XmlElement;

class complexType extends XmlElement
{
    protected $_allowedAttributes = [
        'name',
    ];

    /** @var sequence */
    public $sequence;

}
