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
class sfCachePeerDomainValidator extends sfValidatorBase
{
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
  protected function configure($options = array(), $messages = array())
  {
    $this->addRequiredOption('choices');        

    $this->setMessage('invalid', '"%value%" is not valid field.');
  }

  /**
   * @see sfValidatorBase
   */
  protected function doClean($value)
  {
    $choices = $this->getOption('choices');
    if ($choices instanceof sfCallable)
    {
        $choices = $choices->call();
    }    
    
    foreach ($value as $v)
    {
        $val_keys = array_keys($v);
        foreach($val_keys as $key){
            if (!self::inChoices($key, $choices))
            {
                throw new sfValidatorError($this, 'invalid', array('value' => $v));
            }
        }
    }
    
    return $value;
  }

  
  static protected function inChoices($value, array $choices = array())
  {
      if(in_array($value,$choices)) return true;
      return false;
  }
}
