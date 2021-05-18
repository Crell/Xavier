<?php
declare(strict_types=1);

namespace Crell\Xavier;

use Crell\Xavier\Parser\SchemaParser;
use Crell\Xavier\Elements\XmlElement;

class TreeGenerator
{
    /** @var XmlElement */
    protected $schemaTree;

    public function __construct(string $schema)
    {
        $parser = new SchemaParser();
        $this->schemaTree = $parser->parse($schema);
    }

    public function generate(string $directory, string $phpNs) : void
    {

    }
}
