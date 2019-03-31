<?php
declare(strict_types=1);

namespace Crell\Xavier\Classifier;


class PropertyDefinition
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $visibility = 'public';

    /** @var string */
    protected $type;

    /** @var mixed */
    protected $default;

    public function __construct(string $name, string $visibility = 'public', string $type = null, $default = null)
    {
        assert(in_array($visibility, ['public', 'private', 'protected']));
        $this->name = $name;
        $this->visibility = $visibility;
        $this->type = $type;
    }

    public function __toString()
    {
        $out = '';

        if ($this->type) {
            $out .= "/** @var {$this->type} */\n";
        }

        $out .= "{$this->visibility} \${$this->name}";

        if ($this->default) {
            $out .= var_export($this->default);
        }

        $out .= ';';

        return $out;
    }
}
