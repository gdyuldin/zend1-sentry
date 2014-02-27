<?php

/**
 * ZendSentry
 * 
 * @category   ZendSentry
 */

namespace ZendSentry;

use Raven_ErrorHandler as RavenErrorHandler;
use ZendSentry\Log\Writer\Sentry;
use \Zend_Controller_Front as Front;

/**
 * @package    ZendSentry\ZendSentry
 */
class ZendSentry extends \Zend_Log
{
    /**
     * @var RavenErrorHandler $ravenErrorHandler
     */
    private $ravenErrorHandler;
    
    /**
     * 
     * Is exception logging on or off
     * @var bool
     */
    public $loggingExceptions = FALSE;
    
    /**
     * 
     * Is javascript error logging on or off
     * @var bool
     */
    public $loggingJavascriptErrors = FALSE;

    /**
     * Constructor takes a ZendSentry_Log_Writer_Sentry instance
     *
     * @param Sentry $writer
     * @param RavenErrorHandler $ravenErrorHandler
     */
    public function __construct(Sentry $writer, RavenErrorHandler $ravenErrorHandler = null)
    {
        parent::__construct($writer);
        $this->ravenErrorHandler = $ravenErrorHandler;
    }

    /**
     * Generic logger
     *
     * @see also Zend_Log::log()
     *
     * @param string|Zend_Controller_Response_Http $input
     * @param int $priority
     */
    public function writeLog($input, $priority = 7)
    {
    	
    	if ($input instanceof \Zend_Controller_Response_Http)
        {
            $exceptions = $input->getException();
            
            foreach ($exceptions as $exception)
            {
                $message   = $exception->getMessage();
                
                parent::log($message, 2);
            }
        }
        else
        {   
            $message = $input;
            
            parent::log($message, $priority);
        }
    }

    /**
     * Wrapper to preserver fluent interface
     *
     * @return ZendSentry
     */
    public function logErrors()
    {
        $this->ravenErrorHandler->registerErrorHandler();
        return $this;
    }
    
    /**
     * 
     * Turns Exception logging on or off
     * 
     * @param bool $toggle
     * @return ZendSentry
     */
    public function logExceptions($toggle = TRUE)
    {
        $this->ravenErrorHandler->registerExceptionHandler();
        $this->registerControllerPlugin('ZendSentry\Controller\Plugin\MonitorExceptions', $toggle);

        return $this;
    }

    /**
     * Turns logging of fatal errors on
     */
    public function logFatalErrors()
    {
        $this->ravenErrorHandler->registerShutdownFunction();
        return $this;
    }

    /**
     *
     * Turns logging of javascript errors on or off
     * Logs all javascript errors that are not caught
     *
     * @param bool $toggle
     *
     * @throws \Exception
     * @return ZendSentry
     */
    public function logJavascriptErrors($toggle = TRUE)
    {
    	if ($toggle)
    	{
    		//we need the view and try to get it from the view renderer
    		$viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
			
    		if (null === $viewRenderer->view)
			{
				try 
				{
					$viewRenderer->init();
				}
				catch (Zend_Exception $exception)
				{
			    	throw new \Exception('Could not init() viewRenderer.');
				}
			}
			
			$view = $viewRenderer->view;
        }

    	//$this->registerControllerPlugin('Monitorix_Controller_Plugin_MonitorJavascript', $toggle);
    	
    	if ($toggle)
    	{
            $config = \Zend_Registry::get('config');
            $publicApiKey = $this->convertKeyToPublic($config->sentry->apiKey);
	    	$view->headScript()->appendScript(sprintf("Raven.config('%s').install();", $publicApiKey));
    	}
    	
    	return $this;
    }

    /**
     * @param string $key
     * @return string $publicKey
     */
    private function convertKeyToPublic($key)
    {
        // Find private part
        $start = strpos($key, ':', 6);
        $end = strpos($key, '@');
        $privatePart = substr($key, $start, $end - $start);

        // Replace it with an empty string
        $publicKey = str_replace($privatePart, '', $key);

        return $publicKey;
    }

    /**
     *
     * Registers the MonitorExceptions plugin with the Zend front controller
     * @param $pluginName
     * @param bool $toggle
     */
    protected function registerControllerPlugin($pluginName, $toggle)
    {
        $frontController = Front::getInstance();
        
        $stackIndex = $this->getLowestFreeStackIndex();
        
        if (TRUE == $toggle)
        {
            $frontController->registerPlugin( new $pluginName, $stackIndex);
        }
        else 
        {
            $frontController->unregisterPlugin($pluginName);
        }
    }
    
    /**
     * 
     * Returns the lowest free Zend_Controller_Plugin stack index above $minimalIndex
     * @param int $minimalIndex
     * 
     * @return int $lowestFreeIndex || $minimalIndex
     */
    protected function getLowestFreeStackIndex($minimalIndex = 101)
    {
    	$plugins = \Zend_Controller_Front::getInstance()->getPlugins();
    	$usedIndices = array();
    	
    	foreach ($plugins as $stackIndex => $plugin)
    	{
    		$usedIndices[$stackIndex] = $plugin;
    	}
    	
		krsort($usedIndices);
		
    	$highestUsedIndex = key($usedIndices);
    	
    	if ($highestUsedIndex < $minimalIndex)
    	{
    		return $minimalIndex;
    	}
    	
    	$lowestFreeIndex = $highestUsedIndex + 1;
    	
    	return $lowestFreeIndex;
    	
    }
}