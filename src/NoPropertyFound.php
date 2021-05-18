<?php
declare(strict_types=1);

namespace Crell\Xavier;

class NoPropertyFound extends \RuntimeException
{
    public static function create(string $parentElement, string $childElement): static
    {
        $message = sprintf('Element class %s has no property %s', $parentElement, $childElement);
        return new static($message);
    }
}
