<?php
declare(strict_types=1);

namespace Crell\Xavier\Classifier;

/**
 *
 * @todo Add interfaces
 * @todo Add methods
 */
class ClassBuilder
{
    /** @var string */
    protected $className;

    /** @var string */
    protected $namespace;

    /** @var string */
    protected $parent;

    /** @var PropertyDefinition[] */
    protected $properties = [];

    public function __construct(string $className, string $namespace = null, string $parent = null)
    {
        $this->className = $className;
        $this->namespace = $namespace;
        $this->parent = $parent;
    }

    public function addProperty(PropertyDefinition $prop) : self
    {
        $this->properties[] = $prop;

        return $this;
    }

    public function __toString()
    {
        $out = '';

        $stringer = function($x) : string { return (string)$x; };

        $props = implode("\n\n", array_map($stringer, $this->properties));

        $parent = '';
        if ($this->parent) {
            $parent = "extends {$this->parent}";
        }

        $out = <<<END
class {$this->className} {$parent}
{
{$props}
}
END;

        if ($this->namespace) {
            $out = <<<END
namespace {$this->namespace} {
{$out}
}
END;
        }

        return $out;
    }
}
