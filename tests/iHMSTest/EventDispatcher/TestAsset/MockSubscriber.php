<?php

namespace iHMSTest\EventDispatcher\TestAsset;

use iHMS\EventDispatcher\IEventDispatcher;
use iHMS\EventDispatcher\IEventSubscriber;
use iHMS\EventDispatcher\Listener\EventListener;
use iHMS\EventDispatcher\IEventDescription;

/**
 * MockSubscriber class
 */
class MockSubscriber implements IEventSubscriber
{
    /**
     * @var EventListener[]
     */
    protected $_listeners = array();

    /**
     * Register listeners of this subscriber on the event dispatcher
     *
     * @param IEventDispatcher $eventDispatcher Event dispatcher
     * @return mixed
     */
    public function subscribe(IEventDispatcher $eventDispatcher)
    {
        $listeners = array();
        $listeners[] = $eventDispatcher->addEventListener('foo', array($this, 'onFoo'));
        $listeners[] = $eventDispatcher->addEventListener('bar', array($this, 'onBar'));

        $this->_listeners[spl_object_hash($eventDispatcher)] = $listeners;

        return __METHOD__;
    }

    /**
     * Unregister listeners of this subscriber from the event manager
     *
     * @param IEventDispatcher $eventDispatcher Event manager
     * @return mixed
     */
    public function unsubscribe(IEventDispatcher $eventDispatcher)
    {
        foreach ($this->_listeners[spl_object_hash($eventDispatcher)] as $listener) {
            $eventDispatcher->removeEventListener($listener);
        }

        return __METHOD__;
    }

    /**
     * onFoo listener callback
     *
     * @param IEventDescription $event
     * @return string
     */
    public function onFoo(IEventDescription $event)
    {
        return __METHOD__;
    }

    /**
     * onBar listener callback
     *
     * @param IEventDescription $event
     * @return string
     */
    public function onBar(IEventDescription $event)
    {
        return __METHOD__;
    }
}
