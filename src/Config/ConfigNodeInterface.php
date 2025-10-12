<?php

namespace Concept\Singularity\Config;

interface ConfigNodeInterface
{
    const NODE_ASTERISK = '*';
    const NODE_SINGULARITY = 'singularity';

    const NODE_NAMESPACE = 'namespace';
    const NODE_PACKAGE = 'package';
    const NODE_REQUIRE = 'require';
    const NODE_PREFERENCE = 'preference';
    const NODE_CLASS = 'class';
    const NODE_PARAMETERS = 'parameters';
    const NODE_ARGUMENTS = 'arguments';
    
    //const NODE_ARGUMENTS = 'args';
    const NODE_PREFERENCE_PLUGIN = 'plugin';
    const NODE_ENABLED = 'enabled';

    const NODE_SETTINGS = 'settings';
    const NODE_CACHE = 'cache';
    const NODE_PLUGIN_MANAGER = 'plugin-manager';
    const NODE_PLUGINS = 'plugins';
    const NODE_STRATEGY = 'strategy';
    
    
    const NODE_SERVICE_ID = 'serviceId';
    const NODE_DEPENDENCY_STACK = 'dependencyStack';
    const NODE_SERVICE_REFLECTION = 'reflection';
    const NODE_PRIORITY = 'priority';

    const NODE_COMPOSER_EXTRA = 'concept';
   
}