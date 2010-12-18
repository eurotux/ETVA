<?php

/**
 * This file is part of the dsExtDirectPlugin
 *
 * @package   dsExtDirectPlugin
 * @author    Daniel Stevens <danhstevens@gmail.com>
 * @copyright Copyright (c) 2009, Daniel Stevens
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   SVN: $Id$
 */

/**
 * dsExtDirectController is the manager for the Ext.Direct interface
 *
 * @package    dsExtDirectPlugin
 * @author     Daniel Stevens <danhstevens@gmail.com>
 */

class dsExtDirectController extends sfWebController
{
  protected $resultAdapter = null;
  
  /**
   * Initializes this controller.
   *
   * @param sfContext $context A sfContext implementation instance
   */
  public function initialize($context)
  {
    parent::initialize($context);
    
    //Allow many forwards since a single request may constitute several forwards
    $this->maxForwards = 50;
  }
  
  /**
   * Gets the result adapter for the action
   *
   * @return dsAbstractResultAdapter
   */
  public function getResultAdapter()
  {
    if(is_null($this->resultAdapter))
    {
      $result = sfConfig::get(sprintf('mod_%s_%s_result', $this->context->getModuleName(), $this->context->getActionName()), array());
      $class = isset($result['class']) ? $result['class'] : 'dsPropertyResultAdapter';
      $param = isset($result['param']) ? $result['param'] : array();

      $adapter = new $class($param);
      
      $this->resultAdapter = $adapter instanceof dsAbstractResultAdapter ? $adapter : new dsPropertyResultAdapter();
    }
    
    return $this->resultAdapter;
  }
  
  public function getRenderMode()
  {
    return $this->getResultAdapter()->getRenderMode();
  }
  
  /**
   * Handles RPC requests & sends response
   */
  public function dispatch()
  {
    if (sfConfig::get('sf_logging_enabled'))
    {
      $this->context->getEventDispatcher()->notify(new sfEvent($this, 'application.log', array("Starting dsExtDirectRouter.")));
    }
    
    try
    {
      dsExtDirectRouter::handle();
    }
    catch (Exception $e)
    {
      echo $e->getMessage();
    }
  }
  
  /**
   * Runs RPC actions
   *
   * @param mixed $cdata
   * 
   * @return stdClass Response Object
   */
  public function invokeRpcAction($cdata)
  {
    // if we are in here, it is safe to switch the error handler to our own
    set_error_handler(array('dsExtDirectErrorHandler', 'handleError'));

    //Load API Specs
    $api = dsExtDirectApi::getInstance();
    
    // Response object
    $response = new stdClass();
    
    try
    {
      // Fetch action (in symfony: a 'module')
      $action = $cdata->action;
      if(isset($api[$action]))
      {
        $apiAction = $api[$action];
        
        //Actual symfony name of action (if overridden by extdirect-action)
        $realAction = isset($api[$action]['action']) ? $api[$action]['action'] : $action;
      }
      else 
      {
        throw new Exception('Call to undefined action: ' . $action);
      }
      
      // Fetch method (in symfony: an 'action')
      $method = $cdata->method;
      if(isset($apiAction['methods'][$method]))
      {
        $apiMethod = $apiAction['methods'][$method];
        
        //Actual symfony name of method (if overridden by extdirect-method)
        $realMethod = isset($apiAction['method_map'][$method]) ? $apiAction['method_map'][$method] : $apiMethod;
      }
      else
      {
        throw new Exception("Call to undefined method: $method on action: $action");
      }
      
      $response->type = 'rpc';
      $response->tid = isset($cdata->tid) ? $cdata->tid : null;
      $response->action = $action;
      $response->method = $method;
      
      //Populate request parameters
      if(!dsExtDirectRouter::isForm())
      {
        $this->context->getRequest()->getParameterHolder()->clear();
        
        if(isset($cdata->data) && is_array($cdata->data))
        {
          foreach ($cdata->data[0] as $key => $val)
          {
            $this->context->getRequest()->setParameter($key, $val);
          }
        }
      }
      
      //Call symfony action
      if (sfConfig::get('sf_logging_enabled'))
      {
        $this->context->getEventDispatcher()->notify(new sfEvent($this, 'application.log', array(sprintf('Forwarding to "%s/%s".', $realAction, $realMethod))));
      }
      $this->forward($realAction, $realMethod);
      
      $response->result = $this->getResult($realMethod, $realAction);
    }
    catch (sfStopException $e)
    {
      //If we're in sfStopException, then the action or a filter has probably called a forward
      //so let's try to grab a result anyways and keep on truckin'
      try
      {
        $response->result = $this->getResult($realMethod, $realAction);
      }
      catch (Exception $e)
      {
        $response = $this->generateException($response, $e);
      }
    }
    catch (Exception $e)
    {
      $response = $this->generateException($response, $e);
    }
    
    return $response;
  }
  
  /**
   * Gets the result data via the defined result adapter
   *
   * @param string $method
   * @param string $action
   * @return mixed Result data
   */
  protected function getResult($method, $action)
  {
    //Get the action
    $actionInstance = $this->getActionStack()->getLastEntry()->getActionInstance();
      
    //Throw an exception if we've reached the 404 module
    if($actionInstance->getModuleName() == sfConfig::get('sf_error_404_module') && $actionInstance->getActionName() == sfConfig::get('sf_error_404_action'))
    {
      throw new sfError404Exception("Call to undefined method: $method on action: $action");
    }
    
    return $this->getResultAdapter()->getResult($actionInstance);
  }
  
  /**
   * Generates an exception response object in an Ext.Direct-friendly format
   *
   * @param stdClass $response
   * @param Exception $e
   * @return stdClass Response
   */
  protected function generateException(stdClass $response, Exception $e)
  {
    $response->type = 'exception';

    if(sfConfig::get('sf_debug')) { // show the trace and message only if we are debugging
        
      $response->message = $e->getMessage();
        
      if(sfConfig::get('app_ds_ext_direct_plugin_full_exceptions')) { 
        // fancy exceptions will have the 'where' field structured thus:
        // where: [{
        //          file: file name,
        //          line: line number,
        //          function: function name,
        //          args: arguments as array
        //        }]
        // each element is an individual call on the stack
        $response->where = $e->getTrace();
      }
      else
      {
        $response->where = $e->getTraceAsString();
      }
    }
    else
    {
      $response->message = null;
      $response->where = null;
    }
    
    return $response;
  }
  
  /**
   * Redirect not supported
   *
   * @see sfWebController
   */
  public function redirect($url, $delay = 0, $statusCode = 302)
  {
    
  }
}

?>
