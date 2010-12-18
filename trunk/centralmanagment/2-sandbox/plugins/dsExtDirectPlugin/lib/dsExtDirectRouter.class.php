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
 * dsExtDirectRouter is a symfony implementation of the Ext JS 3.x Direct RPC Router.
 *
 * @package    dsExtDirectPlugin
 * @author     Daniel Stevens <danhstevens@gmail.com>
 */
class dsExtDirectRouter
{
  protected static $isForm = false;
  protected static $isUpload = false;
  protected static $data;
  protected static $response;

  public static function handle()
  {
    $sfContext = sfContext::getInstance();
    $sfRequest = $sfContext->getRequest();
    $sfResponse = $sfContext->getResponse();
    
    // Verify request method is POST
    if($sfRequest->getMethod() != sfWebRequest::POST)
    {
      throw new Exception('Invalid request.');
    }
    
    // Check for form and/or upload action type
    $extAction = $sfRequest->getParameter('extAction');
    if(! empty($extAction))
    {
      self::$isForm = true;
      
      // Is this an upload?
      $extUpload = $sfRequest->getParameter('extUpload');
      self::$isUpload = $extUpload == "true";
      
      //Create a data object
      self::$data = new stdClass();
      self::$data->action = $sfRequest->getParameter('extAction');
      self::$data->method = $sfRequest->getParameter('extMethod');
      self::$data->tid = $sfRequest->getParameter('extTID');
      self::$data->data = array($_POST, $_FILES);
    }
    // Not a form submit, pull and decode raw POST data
    else
    {
      $sfResponse->setHttpHeader('Content-Type', 'text/javascript');
      
      if(isset($GLOBALS['HTTP_RAW_POST_DATA']))
      {
        self::$data = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
      }
      else
      {
        self::$data = json_decode(file_get_contents('php://input'));
      }
    }
    
    if(empty(self::$data))
    {
      throw new Exception('Invalid request.');
    }
    
    // Do RPC(s)
    if(is_array(self::$data))
    {
      foreach(self::$data as $data)
      {
        self::$response[] = sfContext::getInstance()->getController()->invokeRpcAction($data);
      }
    }
    else
    {
      self::$response = sfContext::getInstance()->getController()->invokeRpcAction(self::$data);
    }
    
    // Return results
    if(self::$isForm && self::$isUpload)
    {
      echo '<html><body><textarea>'.json_encode(self::$response).'</textarea></body></html>';
    }
    else 
    {
      echo json_encode(self::$response);
    }
  }

  public static function isForm()
  {
    return self::$isForm;
  }
}

?>