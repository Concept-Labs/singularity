<?php
namespace Concept\Singularity\Context;

use Concept\Config\ConfigurableInterface;

interface ContextBuilderInterface extends ConfigurableInterface
{
    /**
     * Build a context
     * 
     * @param string $serviceId       The service identifier.
     * @param array  $dependencyStack Optional. 
     *                                The dependency stack to use to find appropriate preference.
     *                                Same service identifier may have different preference 
     *                                  depending on the dependency stack.
     *                                See Configuration documentation for more information.
     * @param array  $configOverrides The config overrides.
     * 
     * @return ProtoContextInterface       The builded context based on found preference nodes.
     */
    public function build(string $serviceId, array $dependencyStack = [], array $configOverrides = []): ProtoContextInterface;
}