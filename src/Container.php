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
     * @var array<string, string> Key is the tag name, value is an array of service names
     */
    protected array $tags = [];

    /**
     * @var array<string, array<int, string>> Key is the service name and value is array of tags
     */
    protected array $tagsReverse = [];

    /**
     * @var array<string, array<int, callable>>
     */
    protected array $decorator = [];

    /** @var array<int, callable> */
    protected array $globalDecorator = [];

    /**
     * @param string $id
     * @return mixed
     * @throws ServiceNotFoundException
     */
    public function get(string $id): mixed
    {
        if (!isset($this->services[$id])) throw new ServiceNotFoundException("Service '$id' not found.");
        $callable = $this->services[$id];
        unset($this->services[$id]); // prevent circular dependency
        try {
            $service = $callable($this);

            foreach($this->decorator[$id] ?? [] as $decorator) {
                $service = $decorator($this, $service);
            }
            foreach($this->globalDecorator as $globalDecorator) {
                $service = $globalDecorator($this, $service, $id, $this->tagsReverse[$id] ?? []);
            }
        } finally {
            $this->services[$id] = $callable; // re add service initiator after fetching service
        }
        return $service;
    }

    /**
     * Static code analysis compatible wrapper around method `get`.
     * The method `get` is compatible with any type (e.g. scalar types). Since it's not possible to express this with
     * a type template (generic), I decided to define a separate function only usable for fetching objects from container.
     *
     * @template T
     * @param class-string<T> $id
     * @return T
     * @throws ServiceNotFoundException
     */
    public function getObject(string $id): mixed
    {
        $result = $this->get($id);
        assert($result instanceof $id);
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
     * @param Type $type
     * @throws ServiceAlreadyDefinedException
     */
    public function add(string $id, callable $callable, array $tags = [], Type $type = Type::SINGLETON): void
    {
        $this->throwIfExists($id);

        $this->services[$id] = match ($type) {
            Type::SINGLETON => function (self $container) use ($callable) {
                static $result = null;
                if ($result === null) {
                    $result = $callable($container);
                }
                return $result;
            },
            Type::FACTORY => $callable,
        };

        $this->addTags($id, $tags);
    }

    public function decorate(string $id, callable $callable, Type $type = Type::SINGLETON): void
    {
        $item = match($type) {
            Type::SINGLETON => function(Container $container, mixed $inner) use ($callable) {
                static $result = null;
                if ($result === null) {
                    $result = $callable($container, $inner);
                }
                return $result;
            },
            Type::FACTORY => $callable,
        };

        if (isset($this->decorator[$id])) {
            $this->decorator[$id][] = $item;
        } else {
            $this->decorator[$id] = [$item];
        }
    }

    public function decorateAll(callable $callable, Type $type = Type::SINGLETON): void
    {
        $this->globalDecorator[] = match ($type) {
            Type::SINGLETON => function(Container $container, mixed $inner, string $serviceId, array $tags) use ($callable) {
                static $result = [];
                if (isset($result[$serviceId])) {
                    return $result[$serviceId];
                }
                return $result[$serviceId] = $callable($container, $inner, $serviceId, $tags);
            },
            Type::FACTORY => $callable
        };
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

        if (isset($this->tagsReverse[$serviceId])) {
            $this->tagsReverse[$serviceId] = array_merge($this->tagsReverse[$serviceId], $tags);
        } else {
            $this->tagsReverse[$serviceId] = $tags;
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