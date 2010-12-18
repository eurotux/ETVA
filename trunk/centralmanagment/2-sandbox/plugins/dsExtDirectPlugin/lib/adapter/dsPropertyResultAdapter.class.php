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
 * dsPropertyResultAdapter gets the results from an action from the $this->result property
 *
 * @package    dsExtDirectPlugin
 * @author     Daniel Stevens <danhstevens@gmail.com>
 */

class dsPropertyResultAdapter extends dsAbstractResultAdapter
{
  /**
   * The default result property name
   */
  const DEFAULT_RESULT_PROPERTY = 'result';

  /**
   * The name of the result property
   *
   * @var string
   */
  protected $resultProperty;
  
  /**
   * Initializes the result property
   * Parameters:
   *   property: name of the result property
   *
   * @param array $parameters
   */
  public function __construct($parameters = array())
  {
    $this->resultProperty = isset($parameters['property']) ? $parameters['property'] : self::DEFAULT_RESULT_PROPERTY;
  }
  
  /**
   * Gets the name of the result property
   *
   * @return string
   */
  public function getResultProperty()
  {
    return $this->resultProperty;
  }
  
  /**
   * @see dsAbstractResultAdapter::getResult
   */
  public function getResult(sfAction $action)
  {
    $result = null;
    $vars = $action->getVarHolder()->getAll();
    
    if(isset($vars[$this->getResultProperty()]))
    {
      $result = $vars[$this->getResultProperty()];
    }
    else 
    {
      throw new Exception("No result property set in module '" . $action->getModuleName() . "' on action '" . $action->getActionName() . "'.");
    }
    
    return $result;
  }
}

?>