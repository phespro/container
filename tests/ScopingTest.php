<?php

use Phespro\Container\Type;
use Phespro\Container\Container;

class ScopingTest extends \PHPUnit\Framework\TestCase
{
    public function test_scopedServices(): void
    {
        $container = new Container();

        $container->add('some_id', fn() => rand(1, PHP_INT_MAX), type: Type::SCOPED);

        $service1 = $container->get('some_id');
        $service2 = $container->get('some_id');
        $this->assertEquals($service1, $service2);

        $scopedContainer = $container->newScope();
        $service3 = $scopedContainer->get('some_id');
        $service4 = $scopedContainer->get('some_id');
        $this->assertNotEquals($service1, $service3);
        $this->assertEquals($service3, $service4);
    }

    public function test_singleton(): void
    {
        $container = new Container();
        $container->add('some_id', fn() => rand(1, PHP_INT_MAX), type: Type::SINGLETON);

        $service1 = $container->get('some_id');
        $service2 = $container->newScope()->get('some_id');
        $this->assertEquals($service1, $service2);
    }

    public function test_factory(): void
    {
        $container = new Container();
        $container->add('some_id', fn() => rand(1, PHP_INT_MAX), type: Type::FACTORY);
        $scope = $container->newScope();
        $service1 = $scope->get('some_id');
        $service2 = $scope->get('some_id');
        $this->assertNotEquals($service1, $service2);
    }
}