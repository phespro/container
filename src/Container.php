<?php


namespace Phespro\Container;


use Closure;

class Container implements ContainerInterface
{
    /**
     * @var array<string, Declaration> Key is the service name, value is declaration (initializer + type)
     */
    protected(set) array $services = [];

    /**
     * @var array<string, string> Key is the tag name, value is an array of service names
     */
    protected(set) array $tags = [];

    /**
     * @var array<string, array<int, string>> Key is the service name and value is array of tags
     */
    protected(set) array $tagsReverse = [];

    /**
     * @var array<string, array<int, callable>>
     */
    protected(set) array $decorator = [];

    /** @var array<int, callable> */
    protected(set) array $globalDecorator = [];

    protected(set) array $resolved = [];

    public function __construct(
        protected readonly ?Container $originalScope = null,
    ) {}

    /**
     * @param string $id
     * @return mixed
     * @throws ServiceNotFoundException
     * @throws CircularDependencyException
     */
    public function get(string $id): mixed
    {
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        if (!isset($this->services[$id])) throw new ServiceNotFoundException("Service '$id' not found.");
        $declaration = $this->services[$id];

        if ($declaration->currentInResolve) {
            throw new CircularDependencyException("Circular service dependency detected for service '$id'");
        }
        $declaration->currentInResolve = true;

        try {
            $service = $declaration->resolve($this);

            foreach($this->decorator[$id] ?? [] as $decorator) {
                $service = $decorator($this, $service);
            }
            foreach($this->globalDecorator as $globalDecorator) {
                $service = $globalDecorator($this, $service, $id, $this->tagsReverse[$id] ?? []);
            }

            if ($declaration->type !== Type::FACTORY) {
                $this->resolved[$id] = $service;
            }
        } finally {
            $declaration->currentInResolve = false;
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
     * @throws ServiceNotFoundException|CircularDependencyException
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
     * @param Closure $initializer
     * @param array $tags
     * @param Type $type
     * @throws ServiceAlreadyDefinedException
     */
    public function add(string $id, Closure $initializer, array $tags = [], Type $type = Type::SINGLETON): void
    {
        $this->throwIfExists($id);
        $this->services[$id] = new Declaration(
            $initializer,
            $type,
        );
        $this->addTags($id, $tags);
    }

    public function decorate(string $id, callable $callable): void
    {
        if (isset($this->decorator[$id])) {
            $this->decorator[$id][] = $callable;
        } else {
            $this->decorator[$id] = [$callable];
        }
    }

    public function decorateAll(callable $callable): void
    {
        $this->globalDecorator[] = $callable;
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

    public function newScope(): Scope
    {
        return new Scope($this);
    }
}
