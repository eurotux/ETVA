<?php

class ValidatorIP extends sfValidatorBase
{
  private $error;

  protected function configure($options = array(), $messages = array())
  {    
    $this->setMessage('invalid', '"%value%" is not an valid IP.');
  }

  /**
   * @see sfValidatorBase
   */
  protected function doClean($value)
  {
  
    if(@inet_pton($value)){
        return $value;
    }
    throw new sfValidatorError($this, 'invalid', array('value' => $value));
    
  }

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
