<?php

class RestrictionAclForm extends myBaseForm
{
  
  protected static $invalid_message = '"%value%" is not valid field.';

  public function configure()
  {      
      
      $this->setValidators(array(
            'index' => new sfValidatorNumber(array('required' => false)),
            'allow' => new sfValidatorRegex(
                            array('pattern' =>'#^1$#','required'=>false),
                            array('invalid'=>self::$invalid_message)),
            'deny' => new sfValidatorRegex(
                            array('pattern' =>'#^1$#','required'=>false),
                            array('invalid'=>self::$invalid_message)),
            'match' => new ValidatorArray(array('required' => false)),
            'dontmatch' => new ValidatorArray(array('required' => false))
          ));
      
  }
}
