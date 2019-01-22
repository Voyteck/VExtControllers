<?php

namespace Voyteck\VExtControllers;

class ConfigProvider
{
    /**
     * Retrieve zend-db default configuration.
     *
     * @return array
     */
    public function __invoke() {
        return [
            'testComposerConfig' => "testComposerConfigValue",
//             'controllers' => [
//                 'abstract_factories' => [
//                     \Voyteck\VExtControllers\Factory\LazyControllerFactory::class
//                 ]
//             ]
        ];
    }
}