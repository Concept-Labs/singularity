<?php

namespace Concept\Singularity\Config;

interface ConfigNodeInterface
{
    const NODE_SINGULARITY = 'singularity';

    const NODE_NAMESPACE = 'namespace';
    const NODE_PACKAGE = 'package';
    const NODE_DEPENDS = 'depends';
    const NODE_PREFERENCE = 'preference';
    //const NODE_REFERENCE = 'reference';
    const NODE_CLASS = 'class';
    const NODE_PREFERENCE_PLUGIN = 'plugin';

    const NODE_SETTINGS = 'settings';
    const NODE_PLUGIN_MANAGER = 'plugin-manager';
    const NODE_PLUGIN_MANAGER_PLUGINS = 'plugins';
    const NODE_ASTERISK = '*';
    const NODE_STRATEGY = 'strategy';
    
    

    
    const NODE_SERVICE_ID = 'serviceId';
    const NODE_DEPENDENCY_STACK = 'dependencyStack';
    const NODE_SERVICE_REFLECTION = 'reflection';
    const NODE_PLUGINS = 'plugins';
    const NODE_PRIORITY = 'priority';
    // const NODE_SERVICE_ID = '___serviceId';
    // const NODE_DEPENDENCY_STACK = '___dependencyStack';
    // const NODE_PREFERENCE_CONFIG = '___preference';
    // const NODE_SERVICE_REFLECTION = '___reflection';
}