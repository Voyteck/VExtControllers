<?php

namespace Voyteck\VExtControllers;

class ConfigProvider
{
    /**
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