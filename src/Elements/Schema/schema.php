<?php
declare(strict_types=1);

namespace Crell\Xavier\Elements\Schema;

use Crell\Xavier\Elements\XmlElement;

class schema extends XmlElement
{

    /** @var annotation */
    public $annotation;

    /** @var array */
    public $complexType = [];

    /** @var array */
    public $simpleType = [];

    /** @var array */
    public $element = [];
}
