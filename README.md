[![Build Status](https://travis-ci.org/phespro/container.svg?branch=main)](https://travis-ci.org/phespro/container)

# phespro/container

Super simple dependency injection container for php.

- LOC < 300
- No cache required
- Includes tagging and decorating
- 100% line coverage & 100% mutation coverage
- Implements PSR-11

## Usage

Install it:

```
composer require phespro/container
```

Create it:

```
<?php

require __DIR__ . '/vendor/autoload.php';
$container = new Container;
```

Use it!!!

## Adding Services

```
$container->add('some_id', fn(Container $c) => new MyService); // register singleton
$container->addFactory('other_id', fn(Container $c) => new OtherService); // register factory
$container->add('tagged_service', fn(Container $c) => new TaggedService,  ['console_command']);
```

## Get Services

```
$container->has('some_id'); // does the service exist?
$container->get('some_id'); // get the service
$container->getByTag('console_command'); // get all services, tagged with 'console_command'
```

## Decorating Services

You can decorate (or overwrite) services:

```
$container->decorate('some_id', fn(Container $c, callable $prev) => new Decorator($prev());

// or decorate it with factory
$container->decorateWithFactory('some_id', fn(Container $c, callable $prev) => new Decorator($prev()));
```

## Scoping

Depending on the type of execution model you are running your PHP app, you might have multiple requests / commands per
process.

In this case you might consider using scoped services. A scoped service is used for one specific execution context.

### Scoping example

```php
<?php

$container = new Container();
$container->add('your-service-id', fn() => new YourService, Type::SCOPED);

$service1 = $scope->get('your-service-id');
$scope = $container->newScope();
$service2 = $scope->get('your-service-id');
$service3 = $scope->get('your-service-id');

// $service1 === $service2 -> false -> different instance
// $service1 === $service3 -> false -> different instance
// $service2 === $service3 -> true -> same instance because it's the same scope
```

