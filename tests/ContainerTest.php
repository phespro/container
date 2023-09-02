<?php

namespace Phespro\Container\Test;

use Phespro\Container\Container;
use Phespro\Container\ServiceAlreadyDefinedException;
use Phespro\Container\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;

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
        $container->addFactory('some_singleton', $fnIncrement);

        $this->assertEquals(0, $container->get('some_singleton'));
        $this->assertEquals(1, $container->get('some_singleton'));
    }

    public function test_addFactory_preventExisting()
    {
        $container = new Container;
        $container->addFactory('test', fn() => 'test');
        $this->expectException(ServiceAlreadyDefinedException::class);
        $container->addFactory('test', fn() => 'test');
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

    public function test_decorate_serviceNotFoundException()
    {
        $this->expectException(ServiceNotFoundException::class);
        (new Container)->decorate('some_id', fn() => 'Hello');
    }

    public function test_decorateWithFactory_serviceNotFoundException()
    {
        $this->expectException(ServiceNotFoundException::class);
        (new Container)->decorateWithFactory('some_id', fn() => 'Hello');
    }

    public function test_decorator()
    {
        $fnDecorator = fn(Container $c, callable $prev) => $prev() + 1;

        $container = new Container();
        $container->add('some_id', fn() => 1);
        $container->decorate('some_id', $fnDecorator);
        $this->assertEquals(2, $container->get('some_id'));
        $this->assertEquals(2, $container->get('some_id'));

        $container->decorate('some_id', $fnDecorator);
        $this->assertEquals(3, $container->get('some_id'));

        $fnDecoratorFactory = function(Container $c, callable $prev) {
            static $x = 0;
            return $prev() + ++$x;
        };
        $container->decorateWithFactory('some_id', $fnDecoratorFactory);
        $this->assertEquals(4, $container->get('some_id'));
        $this->assertEquals(5, $container->get('some_id'));
    }

    public function test_tags()
    {
        $container = new Container;
        $container->add('some_id', fn() => 'Hello World', ['test_tag']);
        $container->add('some_id2', fn() => 'Hello World2', ['test_tag']);
        $container->addFactory('some_id3', fn() => 'Hello World3', ['test_tag']);
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
}
