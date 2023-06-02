<?php declare(strict_types=1);
namespace Morpho\Test\Unit\Tech\Php\FileReflectionTest;

/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
interface IHasServiceManager {
    public function setServiceManager(IServiceManager $serviceManager);
}

interface IServiceManager {
    public function get(string $id);

    public function set(string $id, $service);

    //public function setFactory(string $serviceId, $factory);
}

/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
class ServiceManager implements IServiceManager {
    protected $services = [];

    protected $aliases = [];

    private $loading = [];

    public function __construct(array $services = null) {
        if (null !== $services) {
            foreach ($services as $id => $service) {
                $this->set($id, $service);
            }
        }
    }

    public function set(string $id, $service) {
        $this->services[\strtolower($id)] = $service;
        if ($service instanceof IHasServiceManager) {
            $service->setServiceManager($this);
        }
    }

    /**
     * This method uses logic found in the Symfony\Component\DependencyInjection\Container::get().
     */
    public function get(string $id) {
        // Resolve alias:
        $id = \strtolower($id);
        while (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        if (isset($this->loading[$id])) {
            throw new \RuntimeException(
                \sprintf(
                    "Circular reference detected for the service '%s', path: '%s'",
                    $id,
                    \implode(' -> ', \array_keys($this->loading))
                )
            );
        }
        $this->loading[$id] = true;
        try {
            $this->services[$id] = $service = $this->mkService($id);
        } catch (\Exception $e) {
            unset($this->loading[$id]);
            throw $e;
        }
        unset($this->loading[$id]);

        return $service;
    }

    public function setAliases(array $aliases) {
        $this->aliases = $aliases;
    }

    public function setAlias(string $alias, string $name) {
        $this->aliases[$alias] = $name;
    }

    protected function beforeCreate(string $id) {
        // Do nothing by default.
    }

    protected function afterCreate(string $id, $service) {
        if ($service instanceof IHasServiceManager) {
            $service->setServiceManager($this);
        }
    }

    protected function mkService($id) {
        $method = 'new' . $id . 'Service';
        if (\method_exists($this, $method)) {
            $this->beforeCreate($id);
            $service = $this->$method();
            $this->afterCreate($id, $service);
            return $service;
        }
        throw new ServiceNotFoundException($id);
    }
}

/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
class ServiceNotFoundException extends \Exception {
    public function __construct($id) {
        parent::__construct("The service with ID '$id' was not found");
    }
}

/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
trait THasServiceManager {
    protected $serviceManager;

    public function setServiceManager(IServiceManager $serviceManager) {
        $this->serviceManager = $serviceManager;
        return $this;
    }
}
