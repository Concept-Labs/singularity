<?php
namespace Concept\Singularity\Registry;

use Psr\Container\NotFoundExceptionInterface;
use WeakReference;

class ServiceRegistry implements ServiceRegistryInterface
{

    /**
     * @var array<string, object|\WeakReference<object>>
     */
    private array $services = [];

    /**
     * {@inheritDoc}
     */
    public function get(string $id)
    {
        $service = $this->services[$id] ?? null;

        if ($service instanceof WeakReference) {
            $service = $service->get();
            if ($service === null) {
                unset($this->services[$id]);
            }
        }

        // if ($service instanceof PrototypableInterface) {
        //     $service = $service->prototype();
        // }

        return $service ?? throw new NotFoundExceptionInterface(
            sprintf(
                'Service %s not found',
                $id
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        $service = $this->services[$id] ?? null;

        if ($service instanceof WeakReference) {
            $service = $service->get();
            if ($service === null) {
                unset($this->services[$id]);
            }
        }

        return isset($this->services[$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function register(string $id, object $service, bool $weak = false): static
    {
        if ($this->has($id)) {
            throw new \RuntimeException("Service $id already registered");
        }

        $this->services[$id] = $weak ? WeakReference::create($service) : $service;

        return $this;
    }
}