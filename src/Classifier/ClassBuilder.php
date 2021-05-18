<?php
declare(strict_types=1);

namespace Crell\Xavier\Classifier;

/**
 *
 * @todo Add interfaces
 * @todo Add methods
 */
class ClassBuilder implements \Stringable
{

    /** @var PropertyDefinition[] */
    protected array $properties = [];

    public function __construct(
        protected string $className,
        protected ?string $namespace = null,
        protected ?string $parent = null,
    ) {}

    public function addProperty(PropertyDefinition $prop): static
    {
        $this->properties[] = $prop;

        return $this;
    }

    public function fqcn(): string
    {
        return "\\{$this->namespace}\\{$this->className}";
    }

    protected function stringer(mixed $x): string
    {
        return (string)$x;
    }

    public function __toString(): string
    {
        $props = implode("\n\n", array_map([$this, 'stringer'], $this->properties));

        $parent = '';
        if ($this->parent) {
            $parent = " extends \\{$this->parent}";
        }

        $out = <<<END
class {$this->className}{$parent}
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
