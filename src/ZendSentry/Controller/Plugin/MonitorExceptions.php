<?php

namespace ZendSentry\Controller\Plugin;

use \Zend_Controller_Plugin_Abstract as ZendControllerPluginAbstract;
use \Zend_Registry as Registry;
use ZendSentry\ZendSentry;

/**
 * ZendSentry
 *
 * This source file is part of the zend1-sentry package
 *
 * @category   ZendSentry
 */

/**
 * Zend Front Controller Plugin that intercepts Exceptions for logging
 *
 * @category   ZendSentry
 */
class MonitorExceptions extends ZendControllerPluginAbstract
{
    /**
     *
     * Zend Framework provided front controller hook
     * Here used to intercept uncaught Exceptions at the end of the dispatch process
     */
    public function dispatchLoopShutdown()
    {
        $response = $this->getResponse();

        $monitor = Registry::get('monitor'); /** @var $monitor ZendSentry */

        if ($response->isException())
        {
            $monitor->writeLog($response);
        }
    }
}