<?php
/**
 * iHMS - internet Hosting Management system
 * Copyright (C) 2012 by iHMS Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iHMS
 * @package     iHMS_Kernel
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Kernel;

use iHMS\EventDispatcher\IEventDispatcherAware;
use iHMS\Config\Config;
use iHMS\ServiceLocator\IServiceLocator;
use iHMS\EventDispatcher\IEventDispatcher;
use iHMS\ServiceLocator\ServiceLocator;
use iHMS\Library\IMessage as Request;
use iHMS\Library\IMessage as Response;

/**
 * Kernel class
 *
 * @package     iHMS_Kernel
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class Kernel implements IEventDispatcherAware
{
    /**
     * @var Config Config
     */
    protected $config;

    /**
     * @var IServiceLocator
     */
    protected $serviceLocator;

    /**
     * @var IEventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var KernelEvent
     */
    protected $event;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * Constructor
     *
     * @param array|Config $config
     * @param IServiceLocator $serviceLocator
     */
    public function __construct(Config $config, IServiceLocator $serviceLocator)
    {
        $this->config = $config;
        $this->serviceLocator = $serviceLocator;
        $this->request = $serviceLocator->get('Request');
        $this->response = $serviceLocator->get('Response');
    }

    /**
     * Returns config object
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns service locator
     *
     * @return IServiceLocator
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Returns request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns kernel event
     *
     * @return KernelEvent
     */
    public function getKernelEvent()
    {
        return $this->event;
    }

    /**
     * Implements IEventDispatcherAware interface - Set Event dispatcher instance
     *
     * @param IEventDispatcher $eventDispatcher
     * @return kernel
     */
    public function setEventDispatcher(IEventDispatcher $eventDispatcher)
    {
        $eventDispatcher->addIdentifiers(__CLASS__);
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Implements IEventDispatcherAware interface - Returns event dispatcher instance
     *
     * @return IEventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Bootstrap application
     *
     * @trigger beforeBootstrap(KernelEvent)
     * @trigger bootstrap(KernelEvent)
     * @trigger afterBootstrap(KernelEvent)
     * @return Kernel
     */
    public function bootstrap()
    {
        $serviceLocator = $this->getServiceLocator();
        $eventDispatcher = $this->getEventDispatcher();
        $eventDispatcher->addEventListener(
            KernelEvent::BEFORE_BOOTSTRAP_EVENT, $serviceLocator->get('ModuleHandler')
        );

        // Setup kernel event
        $kernelEvent = new KernelEvent();
        $kernelEvent->setContext($this);
        $kernelEvent
            ->setRequest($this->getRequest())
            ->setResponse($this->getResponse())
            ->setRouter($serviceLocator->get('Router'));

        $eventDispatcher->dispatchEvent(KernelEvent::BEFORE_BOOTSTRAP_EVENT, $kernelEvent);
        $eventDispatcher->dispatchEvent(KernelEvent::BOOTSTRAP_EVENT, $kernelEvent);
        $eventDispatcher->dispatchEvent(KernelEvent::AFTER_BOOTSTRAP_EVENT, $kernelEvent);

        return $this;
    }

    /**
     * Run application
     *
     * @trigger route(KernelEvent)
     * @trigger dispatch(KernelEvent)
     */
    public function run()
    {
        // todo
    }

    /**
     * Convenience method to setup application
     *
     * @throws \InvalidArgumentException in case $config is not an array nor a Config object
     * @param array|Config $config
     * @return Kernel
     */
    public static function setupKernel($config = null)
    {
        if (null !== $config) {
            if (is_array($config)) {
                $config = new Config($config);
            } elseif (!$config instanceof $config) {
                throw new \InvalidArgumentException(
                    sprintf('%s(): expects a config object or an array, received %s', __METHOD__, gettype($config))
                );
            }
        } else {
            $config = new Config;
        }

        // Setup service locator
        $serviceLocator = new ServiceLocator(
            new Service\ServiceLocatorConfigurator($config->get('service_locator', array()))
        );

        // Set Config service
        $serviceLocator->setService('Config', $config);

        /** @var $kernel Kernel */
        $kernel = $serviceLocator->get('Kernel');

        return $kernel->bootstrap();
    }
}
