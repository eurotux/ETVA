<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfValidatorChoice validates than the value is one of the expected values.
 *
 * @package    symfony
 * @subpackage validator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfValidatorChoice.class.php 11970 2008-10-06 06:28:40Z dwhittle $
 */
class ValidatorNumber extends sfValidatorNumber
{
  private $error;
  /**
   * Configures the current validator.
   *
   * Available options:
   *
   *  * choices:  An array of expected values (required)
   *  * multiple: true if the select tag must allow multiple selections
   *
   * @param array $options    An array of options
   * @param array $messages   An array of error messages
   *
   * @see sfValidatorBase
   */
  

  public function validate($value)
  {
      if($value){
          try{
                $value = $this->doClean($value);
            }catch(sfValidatorError $e){
                $this->error = $e;
            }
      }
      return $value;
  }

  public function getError(){
      return $this->error;
  }


}
