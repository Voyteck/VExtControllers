<?php
namespace Voyteck\VExtControllers\Factory;

use \Zend\ServiceManager\Factory\AbstractFactoryInterface;
use \Voyteck\VExtMvc\Factory\AbstractLazyFactory;


/**
 *
 * @author wojte
 *
 * @link http://circlical.com/blog/2016/3/9/preparing-for-zend-f
 *
 */
class LazyControllerFactory extends AbstractLazyFactory implements AbstractFactoryInterface {

    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\AbstractFactoryInterface::canCreate()
     */
    public function canCreate(\Interop\Container\ContainerInterface $container, $requestedName)
    {
        return (strstr($requestedName, '\Controller') !== false);
    }

    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(\Interop\Container\ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $object = $this->createObject($requestedName, $serviceLocator, $this->createConstructorParameters($requestedName, $serviceLocator));

        return $object;

    }

}

