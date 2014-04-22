<?php

namespace ZendSentry\Log\Writer;

use Zend_Config;
use Zend_Log_FactoryInterface;
use Raven_Client as Raven;

class Sentry extends \Zend_Log_Writer_Abstract {

    /**
     * Translates Zend Framework log levels to Raven log levels.
     */
    private $logLevels = array(
        'DEBUG'     => Raven::DEBUG,
        'INFO'      => Raven::INFO,
        'NOTICE'    => Raven::INFO,
        'WARN'      => Raven::WARNING,
        'ERR'       => Raven::ERROR,
        'CRIT'      => Raven::FATAL,
        'ALERT'     => Raven::FATAL,
        'EMERG'     => Raven::FATAL,
    );

    protected $raven;
    protected $config;
    protected $withTrace = false;

    /**
     * @param Raven $raven
     * @param null $config
     */
    public function __construct(Raven $raven, $config = null)
    {
        $this->raven = $raven;
        $this->config = $config;
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        if (is_array($config) && isset($config['withTrace'])) {
            $this->withTrace = $config['withTrace'];
        }
    }

    /**
     * Write a message to the log.
     *
     * @param  array $event log data event
     * @return void
     */
    protected function _write($event)
    {
        $this->raven->captureMessage($event['message'], array(), $this->logLevels[$event['priorityName']], $this->withTrace, $event['timestamp']);
    }

    /**
     * Construct a Zend_Log driver
     *
     * @param  array|Zend_Config $config
     * @throws \Exception
     * @return Zend_Log_FactoryInterface
     */
    static public function factory($config)
    {
        throw new \Exception("The Sentry writer can't be created via factory method.");
    }
}
