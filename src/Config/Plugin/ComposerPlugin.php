<?php
namespace Concept\Singularity\Config\Plugin;

use Concept\Arrays\RecursiveDotApi;
use Concept\Composer\Composer;
use Concept\Config\Parser\ParserInterface;
use Concept\Config\Parser\Plugin\AbstractPlugin;
use Concept\Config\Resource\ResourceInterface;
use Concept\Singularity\Config\ConfigNodeInterface;

class ComposerPlugin extends AbstractPlugin
{

    static protected array $packagesData = [];
    
    /**
     * Check if the value can be processed by the plugin
     * 
     * @param string $value
     * 
     */
    protected function match(string $subject, bool $value): bool
    {
        if (!$value) {
            return false;
        }

        $parts = RecursiveDotApi::path($subject);
        return $parts && str_contains(end($parts), '@composer');
    }

    /**
     * Build the Singularity configuration from the Composer packages
     * This plugin processes the "@composer" directive in the configuration.
     * It gathers all the packages from the Composer vendor directory and builds
     * a configuration structure that includes namespaces and package dependencies.
     * This is useful for integrating Composer packages into the Singularity framework.
     * Built coniguratio structure example:
     * ```php
     * [
     *    ConfigNodeInterface::NODE_SINGULARITY => [
     *       ConfigNodeInterface::NODE_NAMESPACE => [
     *          'Vendor\\Package\\Namespace\\' => [
     *             ConfigNodeInterface::NODE_REQUIRE => [
     *                'vendor/package' => [
     *                   // Preserve empty array for future use
     *                ],
     *             ],
     *          ],
     *       ],
     *       ConfigNodeInterface::NODE_PACKAGE => [
     *          'vendor/package' => [
     *             ConfigNodeInterface::NODE_REQUIRE => [
     *                'vendor/dependency' => [
     *                   // Preserve empty array for future use
     *                ],
     *             ],
     *          ],
     *       ],
     *    ],
     * ]
     * ```
     ** structure example w/o constants (current state (20-07-2025)):
     * ```php
     * [
     *    "singularity" => [
     *       "namespace" => [
     *          'Vendor\\Package\\Namespace\\' => [
     *             "require" => [
     *                'vendor/package' => [
     *                   // Preserve empty array for future use
     *                ],
     *             ],
     *          ],
     *       ],
     *       "package" => [
     *          'vendor/package' => [
     *             "require" => [
     *                'vendor/dependency' => [
     *                   // Preserve empty array for future use
     *                ],
     *             ],
     *          ],
     *       ],
     *    ],
     * ]
     * ```
     *
     * {@inheritDoc}
     */
    public function __invoke(mixed $value, string $path, array &$subjectData, callable $next): mixed
    {
        if (!$this->match($path, (bool)$value)) {
            return $next($value, $path, $subjectData);
        }

        $this->collectPackages();

        $packageRequire = $namespaceRequire = [];
        foreach(static::$packagesData as $packageName => $packageData) {
            /**
             * Build namespaces dependencies
             */
            foreach ($packageData['autoload']['psr-4'] ?? [] as $namespace => $path) {
                $namespaceRequire
                    [$namespace]
                        [ConfigNodeInterface::NODE_REQUIRE][$packageName] = [];
            }

            /**
             * Build packages dependencies
             */
            foreach ($packageData['require'] ?? [] as $requireName => $requireVersion) {
                if (!isset(static::$packagesData[$requireName])) {
                    /**
                     * if require is not in the packages data, skip it. 
                     * it means that require package is not concept-labs compatible
                     */
                    continue;
                }
                $packageRequire[$packageName]
                    [ConfigNodeInterface::NODE_REQUIRE]
                        [$requireName] = [];
                    
            }
        }

        /**
         * @todo
         * Add all requires to namespace node (to speedup searching for requires and not digging into packages)
         * This is needed to have a single place to look for all requires
         */
        // foreach ($namespaceRequire as $namespace => $namespaceData) {
        //     foreach ($namespaceData[ConfigNodeInterface::NODE_REQUIRE] ?? [] as $requireName => $requireData) {
        //         $packageRequires = $packageRequire[$requireName][ConfigNodeInterface::NODE_REQUIRE] ?? [];
        //         foreach ($packageRequires as $packageRequireName => $packageRequireData) {
        //             /**
        //              * If the package require is not in the namespace require, add it
        //              */
        //             if (!isset($namespaceRequire[$namespace][ConfigNodeInterface::NODE_REQUIRE][$packageRequireName])) {
        //                 $namespaceRequire[$namespace][ConfigNodeInterface::NODE_REQUIRE][$packageRequireName] = [];
        //             }
        //         }
        //     }
        // }

        /**
         * Merge the collected data into the subject data
         */
        RecursiveDotApi::merge(
            $subjectData,                            // to the subject data
            [
                ConfigNodeInterface::NODE_NAMESPACE => $namespaceRequire, 
                ConfigNodeInterface::NODE_PACKAGE => $packageRequire
            ],
            ConfigNodeInterface::NODE_SINGULARITY,  // to the singularity configuration node
            RecursiveDotApi::MERGE_COMBINE          // using the merge combine mode to preserve existing data
        );

        //remove the import directive
        unset($subjectData[$path]);

        //let the parser know that the value has been removed
        return ParserInterface::VALUE_TO_REMOVE;
    }


    /**
     * Collect packages data from the Composer vendor directory
     * This method reads all composer.json files in the vendor directory and collects
     * the package data that is compatible with concept-labs.
     * The collected data is stored in the static::$packagesData property.
     * 
     * @return void
     */
    protected function collectPackages(): void
    {
        if (empty(static::$packagesData)) {
            /** * Collect root composer.json data
             * This is needed to include the root package data in the configuration
             * so that it can be used in the application.
            */
            static::collectRootComposer();

            $composerFiles = Composer::getVendorDir() . '/*/*/composer.json';
            $composerFiles = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $composerFiles);

            foreach (glob($composerFiles) as $file) {
                //$packageData = json_decode(file_get_contents($file), true);
                $packageData = [];
                $this->getResource()->read($packageData, $file);

                /**
                 * Check for concept-labs compatibility
                 */
                if (isset($packageData['extra']['concept'])) { 
                    static::$packagesData[$packageData['name']] = $packageData;
                }
            }

            
        }
    }

    protected function collectRootComposer(): void
    {
        $composerFiles = Composer::getVendorDir() . "/../composer.json";
        $composerFiles = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $composerFiles);

        foreach (glob($composerFiles) as $file) {
            //$packageData = json_decode(file_get_contents($file), true);
            $packageData = [];
            $this->getResource()->read($packageData, $file);

            static::$packagesData[$packageData['name']] = $packageData;
        }
    }

    /**
     * Get the resource
     * 
     * @return ResourceInterface
     */
    protected function getResource(): ResourceInterface
    {
        return $this->getConfig()->getResource();
    }
    
}
