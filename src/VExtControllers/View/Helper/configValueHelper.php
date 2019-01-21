<?php
namespace Voyteck\VExtControllers\View\Helper;

use \Zend\View\Helper\AbstractHelper;

class configValueHelper extends AbstractHelper {

    const LAZYFACTORY_INCLUDE_CONFIG = 'Config';

    /**
     * Provides value from Config
     * 
     * @param array $configPath     Array with keys for config path - concurrent elements are deeper levels of config 
     *                                  e.g. ['router', 'routes', 'webmail', 'type'] will result with Config['router']['routes']['webmail']['type']
     * @return unknown              Value of specific entry in configuration
     */
    public function __invoke(array $configPath = [], $quoteStrings = true) {
        $currentConfigValue = $this->Config;
        if (sizeof($configPath) > 0)
            foreach($configPath as $configKey)
                $currentConfigValue = $currentConfigValue[$configKey];
        
        if ($quoteStrings && is_string($currentConfigValue))
            return "'" . addslashes($currentConfigValue) . "'";
        else 
            return $currentConfigValue;
        
    }

}