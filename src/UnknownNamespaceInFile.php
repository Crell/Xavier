<?php
declare(strict_types=1);

namespace Crell\Xavier;

class UnknownNamespaceInFile extends \RuntimeException
{
    public static function create(string $tagNs): static
    {
        $message = sprintf('The short namespace %s is not defined in the root element.', $tagNs);
        return new static($message);
    }
}
