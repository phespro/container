<?php


namespace Phespro\Container;


use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private array $services = [];
    private array $tags = [];

    public function get($id)
    {
        if (!isset($this->services[$id])) throw new ServiceNotFoundException("Service '$id' not found.");
        $fun = $this->services[$id];
        unset($this->services[$id]); // prevent circular dependency
        $result = $fun($this);
        $this->services[$id] = $fun; // re add service function after fetching service
        return $result;
    }

    public function getByTag(string $tag): array
    {
        return array_map(
            fn($id) => $this->get($id),
            $this->tags[$tag] ?? []
        );
    }

    public function has($id)
    {
        return isset($this->services[$id]);
    }

    public function add(string $id, callable $callable, array $tags = []): void
    {
        $this->services[$id] = function (self $container) use ($callable) {
            static $result = null;
            if ($result === null) {
                $result = $callable($container);
            }
            return $result;
        };
        $this->addTags($id, $tags);
    }

    public function addFactory(string $id, callable $callable, array $tags = []): void
    {
        $this->services[$id] = $callable;
        $this->addTags($id, $tags);
    }

    public function decorate(string $id, callable $callable): void
    {
        if (!isset($this->services[$id])) {
            throw new ServiceNotFoundException("You tried decorating service $id, but no such service exists");
        }
        $previous = $this->services[$id];
        $this->services[$id] = function(Container $container) use ($callable, $previous) {
            static $result = null;
            if ($result === null) {
                $result = $callable($container, fn() => $previous($container));
            }
            return $result;
        };
    }

    public function decorateWithFactory(string $id, callable $callable): void
    {
        if (!isset($this->services[$id])) {
            throw new ServiceNotFoundException("You tried decorating service $id, but no such service exists");
        }
        $previous = $this->services[$id];
        $this->services[$id] = fn(Container $container) => $callable($container, fn() => $previous($this));
    }

    private function addTags(string $serviceId, array $tags = [])
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [$serviceId];
            } else {
                $this->tags[$tag][] = $serviceId;
            }
        }
    }
}