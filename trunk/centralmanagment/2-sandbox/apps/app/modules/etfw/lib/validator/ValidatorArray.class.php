<?php


class ValidatorArray extends sfValidatorBase
{
  /**
   * Configures the current validator.
   *
   * Available options:
   *
   *  * choices:  An array of expected values (required)
   *  * assoc: true if the array is associative
   *
   * @param array $options    An array of options
   * @param array $messages   An array of error messages
   *
   * @see sfValidatorBase
   */
  protected function configure($options = array(), $messages = array())
  {
    $this->addOption('choices');    
    $this->addOption('assoc');

    $this->setMessage('invalid', '"%value%" is not valid field.');
    $this->addMessage('type_error', 'Wrong type.');
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
    
    if (!is_array($value))
    {
        throw new sfValidatorError($this, 'type_error');
    }

    if ($this->getOption('assoc'))
    {
        foreach ($value as $k=>$v)
        {

            if (!self::inChoices($k, $choices))
            {
                throw new sfValidatorError($this, 'invalid', array('value' => $k));
            }
        }

    }
    
    return $value;
  }

  /**
   * Checks if a value is part of given choices
   *
   * @param  mixed $value   The value to check
   * @param  array $choices The array of available choices
   *
   * @return Boolean
   */
  static protected function inChoices($value, array $choices = array())
  {
    foreach ($choices as $choice)
    {        
      if ((string) $choice == (string) $value)
      {
        return true;
      }
    }

    return false;
  }
}
