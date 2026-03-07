<?php

namespace Phespro\Container;

enum Type
{
    /**
     * When using SINGLETON, the service will only be initialized once. The same instance of the service is returned for
     * ALL subsequent retrievals.
     */
    case SINGLETON;

    /**
     * When using FACTORY, a new service instance is created, everytime the service is loaded from the DI.
     */
    case FACTORY;

    /**
     * When using SCOPED, the same instance will be returned while you are in the same scope. The scope could either be
     * an http request or terminal call, depending on your implementation.
     */
    case SCOPED;
}
