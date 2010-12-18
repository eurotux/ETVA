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
 * dsAbstractResultAdapter abstract class used to get the results of an action
 *
 * @package    dsExtDirectPlugin
 * @author     Daniel Stevens <danhstevens@gmail.com>
 */

abstract class dsAbstractResultAdapter
{

  /**
   * Initializes result adapater
   *
   * @param array $parameters Adapter specific parameters
   */
  public function __construct($parameters = array())
  {
    
  }
  
  /**
   * Gets the render mode for the adapter
   *
   * @return int
   */
  public function getRenderMode()
  {
    return sfView::RENDER_NONE;
  }
  
  /**
   * Gets the result of an action
   *
   * @param sfAction $action
   */
  public abstract function getResult(sfAction $action);
}

?>