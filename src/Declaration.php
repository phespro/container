<?php

namespace Phespro\Container;

/**
 * @internal Do not use this class in app-code. It's intended to be used inside the container.
 */
class Declaration
{
    public bool $currentInResolve = false;

    public function __construct(
        public readonly \Closure $resolver,
        public readonly Type $type,
    ) { }

    public function resolve(ContainerInterface $container): mixed
    {
        $resolver = $this->resolver;
        return $resolver($container);
    }
}