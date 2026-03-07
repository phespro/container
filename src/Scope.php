<?php

namespace Phespro\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Scope implements ContainerInterface
{
    protected array $resolved = [];

    public function __construct(
        protected readonly Container $container,
    ) { }

    /**
     * @inheritDoc
     */
    public function getObject(string $id): mixed
    {
        return $this->get($id);
    }

    public function getByTag(string $tag): array
    {
        return array_map(
            fn($id) => $this->get($id),
            $this->container->tags[$tag] ?? []
        );
    }

    public function get(string $id)
    {
        $service = $this->container->services[$id] ?? throw new ServiceNotFoundException();

        if ($service->type === Type::SINGLETON) {
            return $this->container->get($id);
        }

        if ($service->type === Type::FACTORY) {
            return $service->resolve($this);
        }

        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        return $this->resolved[$id] = $service->resolve($this);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }
}
