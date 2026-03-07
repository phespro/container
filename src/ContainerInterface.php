<?php

namespace Phespro\Container;

interface ContainerInterface extends \Psr\Container\ContainerInterface
{
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
    public function getObject(string $id): mixed;

    /**
     * Get all services that are tagged with the given tag.
     *
     * @param string $tag
     * @return array
     */
    public function getByTag(string $tag): array;
}