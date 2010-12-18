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
 * dsExtDirectApi API spec singleton.
 *
 * @package    dsExtDirectPlugin
 * @author     Daniel Stevens <danhstevens@gmail.com>
 */

class dsExtDirectApi
{
  protected static $api = null;
  
  public static function getInstance()
  {
    if(is_null(self::$api))
    {
      $env = sfConfig::get('sf_environment');
      $file =  sfConfig::get('sf_app_config_dir') . '/' . $env . '_api.yml';
      if(file_exists($file))
      {
        self::$api = sfYaml::load(file_get_contents($file));
      }
      else
      {
        self::$api = array();
      }
    }
    
    return self::$api;
  }
}

?>