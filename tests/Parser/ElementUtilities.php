<?php
declare(strict_types=1);

namespace Crell\Xavier\Parser;

use Crell\Xavier\Classifier\ClassBuilder;
use Crell\Xavier\Classifier\PropertyDefinition;
use Crell\Xavier\Elements\XmlElement;

trait ElementUtilities
{
    /**
     * Declares a new XmlElement child class into the current process memory.
     *
     * @param string $name
     *   The class/tag name (case sensitive).
     * @param string $namespace
     *   The namespace in which to declare the class.
     * @param array $properties
     *   The public properties this class should have (its child elements).
     * @param array $attributes
     *   An array of legal attributes for this element.
     * @return string
     *   The full class name of the just-declared element class.
     */
    protected function declareElement(string $name, string $namespace, array $properties = [], array $attributes = []) : string
    {
        $b = new ClassBuilder($name, $namespace, XmlElement::class);

        $b->addProperty(new PropertyDefinition('_allowedAttributes', 'protected', 'array', $attributes));

        foreach ($properties as $prop) {
            $b->addProperty(new PropertyDefinition($prop));
        }

        $b->declare();

        return $b->fqcn();
    }
}
