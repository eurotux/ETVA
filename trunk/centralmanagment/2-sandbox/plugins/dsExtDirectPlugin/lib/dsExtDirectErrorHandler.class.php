<?php

/**
 * This file is part of the dsExtDirectPlugin
 *
 * @package   dsExtDirectPlugin
 * @author    Jesse Dhillon <http://deva0.net/contact>
 * @copyright Copyright (c) 2009, Jesse Dhillon
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   SVN: $Id: dsExtDirectErrorHandler.class.php 18811 2009-06-01 17:49:04Z dancablam $
 */

/**
 * dsExtDirectErrorHandler Error handler for PHP-errors (not Exceptions)
 *
 * @package   dsExtDirectPlugin
 * @author    Jesse Dhillon <http://deva0.net/contact>
 */

class dsExtDirectErrorHandler
{ 
  public static function handleError($errno, $errstr, $errfile, $errline, $errcontext = null) {
    // If errors are suppressed or error reporting is not set do not throw error
    if(($errno & error_reporting()) === 0)
    {
      return false;
    }
    
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline); 
    die();
  }
}
