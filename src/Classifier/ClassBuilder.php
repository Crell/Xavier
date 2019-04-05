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

    public function fqcn() : string
    {
        return "\\{$this->namespace}\\{$this->className}";
    }

    protected function stringer($x) : string
    {
        return (string)$x;
    }

    public function __toString()
    {
        $out = '';

        $props = implode("\n\n", array_map([$this, 'stringer'], $this->properties));

        $parent = '';
        if ($this->parent) {
            $parent = "extends \\{$this->parent}";
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

    /**
     * Evaluates this class and declares it in the current process.
     *
     * Warning: This means using eval() to add runable code to the current
     * process. That is a wonderful code injection attack vector unless you're
     * super careful.  Do not let user-provided data anywhere near this class
     * unless you really really know what you're doing.
     *
     * @return string
     *  The FQCN of the class that was just defined.
     */
    public function declare() : string
    {
        eval((string)$this);
        return $this->fqcn();
    }
}
