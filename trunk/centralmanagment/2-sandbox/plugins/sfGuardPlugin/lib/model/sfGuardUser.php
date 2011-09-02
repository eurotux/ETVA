<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfGuardUser.php 7634 2008-02-27 18:01:40Z fabien $
 */
class sfGuardUser extends PluginsfGuardUser
{
  /*
   *
   * CUSTOM setPassword to store passwords as it is!
   *
   */
  public function setPassword($password)
  {

    // store the backtrace
    $bt = debug_backtrace();

    // analyze backtrace to see if importing from fixtures
    $is_importing = false;
    foreach ($bt as $cf)
      if ($cf['function'] == 'loadData')
        $is_importing = true;

    // if importing from fixtures
    // AND specifically instructed to import encrypted passwords
    // then just save the encrypted password
    if ($is_importing && $this->getSalt() &&
        sfConfig::get('app_sf_guard_plugin_import_encrypted_passwords', false))
    {

  		if ($password !== null && !is_string($password)) {
  			$password = (string) $password;
        }

  		if ($this->password !== $password) {
  			$this->password = $password;
  			$this->modifiedColumns[] = sfGuardUserPeer::PASSWORD;
  		}

    } else
      parent::setPassword($password);
  }

}
