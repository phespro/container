<?php

namespace Phespro\Container\Test;

use Phespro\Container\Container;
use Phespro\Container\ServiceAlreadyDefinedException;
use Phespro\Container\ServiceNotFoundException;
use Phespro\Container\Type;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerTest extends TestCase
{
    public function test_singleton()
    {
        $fnIncrement = function() {
            static $x = 0;
            return $x++;
        };

        $container = new Container();
        $container->add('some_singleton', $fnIncrement);

        $this->assertEquals(0, $container->get('some_singleton'));
        $this->assertEquals(0, $container->get('some_singleton'));
    }

    public function test_add_preventExisting()
    {
        $container = new Container;
        $container->add('test', fn() => 'test');
        $this->expectException(ServiceAlreadyDefinedException::class);
        $container->add('test', fn() => 'test');
    }

    public function test_factory()
    {
        $fnIncrement = function() {
            static $x = 0;
            return $x++;
        };

        $container = new Container();
        $container->add('some_singleton', $fnIncrement, type: Type::FACTORY);

        $this->assertEquals(0, $container->get('some_singleton'));
        $this->assertEquals(1, $container->get('some_singleton'));
    }

    public function test_addFactory_preventExisting()
    {
        $container = new Container;
        $container->add('test', fn() => 'test', type: Type::FACTORY);
        $this->expectException(ServiceAlreadyDefinedException::class);
        $container->add('test', fn() => 'test', type: Type::FACTORY);
    }

    public function test_has()
    {
        $id = 'some_id';
        $container = new Container;
        $this->assertFalse($container->has($id), 'If service was not added, has-method should return false');
        $container->add($id, fn() => 'Hello Again');
        $this->assertTrue($container->has($id));
    }

    public function test_get_serviceNotFoundException()
    {
        $this->expectException(ServiceNotFoundException::class);
        (new Container)->get('some_id');
    }

    public function test_decorator()
    {
        $fnDecorator = fn(Container $c, int $inner) => $inner + 1;

        $container = new Container();
        $container->add('some_id', fn() => 1);
        $container->decorate('some_id', $fnDecorator);
        $this->assertEquals(2, $container->get('some_id'));
        $this->assertEquals(2, $container->get('some_id'));

        $container->decorate('some_id', $fnDecorator);
        $this->assertEquals(3, $container->get('some_id'));

        $fnDecoratorFactory = function(Container $c, int $inner) {
            static $x = 0;
            return $inner + ++$x;
        };
        $container->decorate('some_id', $fnDecoratorFactory, Type::FACTORY);
        $this->assertEquals(4, $container->get('some_id'));
        $this->assertEquals(5, $container->get('some_id'));
    }

    public function test_tags()
    {
        $container = new Container;
        $container->add('some_id', fn() => 'Hello World', ['test_tag']);
        $container->add('some_id2', fn() => 'Hello World2', ['test_tag']);
        $container->add('some_id3', fn() => 'Hello World3', ['test_tag'], Type::FACTORY);
        $this->assertEquals(['Hello World', 'Hello World2', 'Hello World3'], $container->getByTag('test_tag'));
    }

    public function test_circularDependencyProtection()
    {
        $container = new Container;
        $container->add('service1', fn(Container $c) => $c->get('service2'));
        $container->add('service2', fn(Container $c) => $c->get('service3'));
        $container->add('service3', fn(Container $c) => $c->get('service1'));
        $this->expectException(ServiceNotFoundException::class);
        $container->get('service1');
    }

    public function test_decorateAll()
    {
        $container = new Container;
        $container->add('service1', fn() => 'hello', ['tag1']);
        $container->decorateAll(
            function(ContainerInterface $container, string $decorated, string $serviceName, array $tags) use (&$hasBeenRun) {
                $this->assertEquals('hello', $decorated);
                $this->assertEquals('service1', $serviceName);
                $this->assertEquals(['tag1'], $tags);
                return $decorated . ' world';
            }
        );
        $this->assertEquals('hello world', $container->get('service1'));
    }
}
