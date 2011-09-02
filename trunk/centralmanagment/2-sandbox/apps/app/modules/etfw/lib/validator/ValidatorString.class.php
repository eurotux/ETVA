<?php


class ValidatorString extends sfValidatorString
{
  private $error;

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
