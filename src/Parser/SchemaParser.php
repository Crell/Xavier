<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

class SchemaParser extends Parser
{

    public function __construct(bool $strict = false)
    {
        parent::__construct('', $strict);

        $this->addNamespace('http://www.w3.org/2001/XMLSchema', 'Crell\Xavier\Elements\Schema');
    }
}
