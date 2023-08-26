<?php


namespace Phespro\Container;


use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * @var array<string, callable> Key is the service name, value is the service initiator
     */
    protected array $services = [];

    /**
     * @var array<string, string> Key is the tag name, value is the service name
     */
    protected array $tags = [];

    public function get(string $id): mixed
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

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * @param string $id
     * @param callable $callable
     * @param array $tags
     * @throws ServiceAlreadyDefinedException
     */
    public function add(string $id, callable $callable, array $tags = []): void
    {
        $this->throwIfExists($id);
        $this->services[$id] = function (self $container) use ($callable) {
            static $result = null;
            if ($result === null) {
                $result = $callable($container);
            }
            return $result;
        };
        $this->addTags($id, $tags);
    }

    /**
     * @param string $id
     * @param callable $callable
     * @param array $tags
     * @throws ServiceAlreadyDefinedException
     */
    public function addFactory(string $id, callable $callable, array $tags = []): void
    {
        $this->throwIfExists($id);
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

    private function addTags(string $serviceId, array $tags = []): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [$serviceId];
            } else {
                $this->tags[$tag][] = $serviceId;
            }
        }
    }

    /**
     * @param string $id
     * @throws ServiceAlreadyDefinedException
     */
    private function throwIfExists(string $id): void
    {
        if (isset($this->services[$id])) {
            throw new ServiceAlreadyDefinedException("You tried to add the service $id, but it already added.");
        }
    }
}