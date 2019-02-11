<?php

namespace Voyteck\VExtControllers;

/**
 * @todo Needs to be adjusted hence right now it is not working - abstract factories needs to be added in application config files
 *
 * @author zielinw1
 *
 */
class ConfigProvider
{
    /**
     *
     * @return array
     */
    public function __invoke() {
        return [
            'controllers' => [
                'abstract_factories' => [
                    \Voyteck\VExtControllers\Factory\LazyControllerFactory::class
                ]
            ]
        ];
    }
}