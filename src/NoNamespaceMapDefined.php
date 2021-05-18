<?php
declare(strict_types=1);

namespace Crell\Xavier;

class NoNamespaceMapDefined extends \RuntimeException
{
    public static function create(string $namespace): static
    {
        $message = sprintf('The XML namespace %s has no corresponding PHP namespace defined.', $namespace);
        return new static($message);
    }
}
