[![Build Status](https://travis-ci.org/phespro/container.svg?branch=main)](https://travis-ci.org/phespro/container)

# phespro/container

Super simple dependency injection container for php.

- Only 121 lines of code
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