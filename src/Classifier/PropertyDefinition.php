<?php
declare(strict_types=1);

namespace Crell\Xavier\Classifier;


class PropertyDefinition implements \Stringable
{
    public function __construct(
        protected string $name,
        protected string $visibility = 'public',
        protected string $type = '',
        protected mixed $default = null,
    ) {
        assert(in_array($visibility, ['public', 'private', 'protected']), 'Visibility must be one of "public", "private", or "protected".');
    }

    public function __toString(): string
    {
        $out = '';

        $out .= "{$this->visibility} {$this->type} \${$this->name}";

        if (!is_null($this->default)) {
            $out .= ' = ' . var_export($this->default, true);
        }

        $out .= ';';

        return $out;
    }
}
