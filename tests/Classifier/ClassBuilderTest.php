<?php
declare(strict_types=1);

namespace Crell\Xavier\Classifier;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ClassBuilderTest extends TestCase
{

    public function test_empty_class_generates_correctly() : void
    {
        $b = new ClassBuilder('Foo');

        $expected = <<<'END'
class Foo 
{

}
END;

        $this->assertEquals($expected, (string)$b);
    }

    public function test_empty_class_in_namespace_generates_correctly() : void
    {
        $b = new ClassBuilder('Foo', 'My\Name\Space');

        $expected = <<<'END'
namespace My\Name\Space {
class Foo 
{

}
}
END;

        $this->assertEquals($expected, (string)$b);
    }

    public function test_parent_class_generates_correctly() : void
    {
        // The ::class magic constant doesn't have a leading \, so don't include one here.
        $b = new ClassBuilder('Foo', 'My\Name\Space', 'Some\ParentClass');

        $expected = <<<'END'
namespace My\Name\Space {
class Foo extends \Some\ParentClass
{

}
}
END;
        $this->assertEquals($expected, (string)$b);
    }

    public function test_properties_generate_correctly() : void
    {
        $b = new ClassBuilder('Foo');

        $b->addProperty(new PropertyDefinition('thing', 'public', 'string'))
            ->addProperty(new PropertyDefinition('stuff', 'protected', 'int'));
        ;

        $expected = <<<'END'
class Foo 
{
/** @var string */
public $thing;

/** @var int */
protected $stuff;
}
END;
        $this->assertEquals($expected, (string)$b);
    }

    public function test_eval_of_generated_class_parses() : void
    {
        $b = (new ClassBuilder('Foo', 'My\Name\Space'))
            ->addProperty(new PropertyDefinition('thing', 'public', 'string'))
            ->addProperty(new PropertyDefinition('stuff', 'protected', 'int'));

        $b->declare();

        $this->assertClassHasAttribute('thing', 'My\Name\Space\Foo');
        $this->assertClassHasAttribute('stuff', 'My\Name\Space\Foo');
    }

    public function test_default_value_parses() : void
    {
        try {
            $b = (new ClassBuilder('Foo', 'My\Name\Space'))
                ->addProperty(new PropertyDefinition('thing', 'public', 'string', 'foo'))
                ->addProperty(new PropertyDefinition('stuff', 'protected', 'int', '5'))
                ->addProperty(new PropertyDefinition('arr', 'public', 'array', ['foo' => 'bar']));

            $b->declare();

            $this->assertClassHasAttribute('thing', 'My\Name\Space\Foo');
        }
        catch (\ParseError $e) {
            $this->fail($e->getMessage());
        }
    }

    public function test_falsy_default_values_parse() : void {
        try {
            $b = (new ClassBuilder('Foo', 'My\Name\Space'))
                ->addProperty(new PropertyDefinition('thing', 'public', 'string', ''))
                ->addProperty(new PropertyDefinition('stuff', 'public', 'int', 0))
                ->addProperty(new PropertyDefinition('arr', 'public', 'array', []));

            $className = $b->declare();

            $test = new $className();

            $this->assertEquals('', $test->thing);
            $this->assertEquals(0, $test->stuff);
            $this->assertEquals([], $test->arr);
        }
        catch (\ParseError $e) {
            $this->fail($e->getMessage());
        }
    }
}
