<?php

class ValidatorDhcpRange extends sfValidatorBase
{

  private static $bootp_flag = 'dynamic-bootp';
  
  protected function configure($options = array(), $messages = array())
  {    

    $this->setMessage('invalid', '"%value%" is not valid field.');
    $this->addMessage('type_error', 'Wrong type.');
  }

  /**
   *
   * value should be array
   *  array('dynamic-bootp 0.0.0.0 0.0.0.0')
   *  or
   *  array('0.0.0.0 0.0.0.0')
   *
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

    $match_bootp = '/^'.self::$bootp_flag.'/';
    
    foreach ($value as $v)
    {

        if(preg_match($match_bootp,$v)){

            $ranges_data = explode(' ',$v,3);

            if(count($ranges_data)!=3)
                throw new sfValidatorError($this, 'invalid', array('value' => $v));

            $dyn_bootp = $ranges_data[0];
            $from_ip = $ranges_data[1];
            $to_ip = $ranges_data[2];

            $validate_bootp = new sfValidatorRegex(
                        array('pattern' =>'#^'.self::$bootp_flag.'#'),
                        array('invalid' => $this->getMessage('invalid')));
            $validate_bootp->doClean($dyn_bootp); // should be dynamic bootp flag            

            $ip = new ValidatorIP();
            $ip->doClean($from_ip);
            $ip->doClean($to_ip);

        }else{

            $ranges_data = explode(' ',$v,2);

            if(count($ranges_data)!=2)
                throw new sfValidatorError($this, 'invalid', array('value' => $v));

            $from_ip = $ranges_data[0];
            $to_ip = $ranges_data[1];

            $ip = new ValidatorIP();
            $ip->doClean($from_ip);
            $ip->doClean($to_ip);
        }

    }    
    return $value;
  }


}
