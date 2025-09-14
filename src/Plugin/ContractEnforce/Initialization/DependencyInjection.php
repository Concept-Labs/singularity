<?php
namespace Concept\Singularity\Plugin\ContractEnforce\Initialization;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Contract\Initialization\InjectableInterface;
use Concept\Singularity\Exception\RuntimeException;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Plugin\Attribute\Injector;

class DependencyInjection extends AbstractPlugin
{
    /**
     * {@inheritDoc}
     */
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        if (!$service instanceof InjectableInterface) {
            throw new RuntimeException(
                sprintf(
                    'Cannot apply dependency injection for service %s. It must implement %s',
                    $context->getServiceClass(),
                    InjectableInterface::class
                )
            );
        }

        $injectMethod = $context->getReflectionMethod(InjectableInterface::INJECT_METHOD);

        if ($injectMethod !== null) {
            $injectMethod->setAccessible(true);
            $deps = static::resolveDependencies($injectMethod, $context);
            $injectMethod->invoke(
                $service,
                ...$deps
            );
        }

        /**
         * Invoke methods marked with #[Injector]
         */
        static::invokeInjectors($service, $context);

    }

    /**
     * Resolve method dependencies
     * 
     * @param \ReflectionMethod $method
     * @param ProtoContextInterface $context
     * 
     * @return array
     */
    protected static function resolveDependencies(\ReflectionMethod $method, ProtoContextInterface $context): array
    {
        $params = $method->getParameters();
        $deps = [];

        foreach ($params as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $deps[] = $context->getContainer()->get($type->getName());
            }
        }

        return $deps;
    }


    /**
     * Invoke injector methods
     * only public methods are considered. why?
     * because to still be able to use them w/o automatic plugin and breaking encapsulation
     * f.e.
     * - using them in a manual way
     * - keeping the control over the instantiation
     * like:
     *  #[Injector]
     *  public function depends(EventDispatcherInterface $eventDispatcher): void
     *
     * @param object $service
     * @param ProtoContextInterface $context
     */
    protected static function invokeInjectors(object $service, ProtoContextInterface $context): void
    {
        $reflection = $context->getReflection();

        if ($reflection === null) {
            return;
        }

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $attributes = $method->getAttributes(Injector::class);

            if (count($attributes) > 0) {
                $deps = static::resolveDependencies($method, $context);
                $method->invoke(
                    $service,
                    ...$deps
                );
            }
        }
    }
}