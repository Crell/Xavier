<?php
declare(strict_types=1);

namespace Crell\Xavier;

class NoElementClassFound extends \RuntimeException
{
    public static function create(string $tag): static
    {
        $message = sprintf('No class defined for XML tag: %s.', $tag);
        return new static($message);
    }
}
