<?php

class myUser extends sfGuardSecurityUser
{




   public function initVncToken()
  {
      $user_id = $this->getGuardUser()->getId();

      // remove previous data
      $c = new Criteria();
      $c->add(EtvaVncTokenPeer::USER_ID, $user_id);     

       EtvaVncTokenPeer::doDelete($c);


      
      // generate new data
      
      $user_name = $this->getUsername();
      $tokens = self::generatePairToken();
      
      $vncToken = new EtvaVncToken();

     
      $vncToken->setUserId($user_id);
      $vncToken->setUsername($user_name);
      $vncToken->setToken($tokens[0]);
      $vncToken->setEnctoken($tokens[1]);
      $vncToken->save();

  }


  protected function generatePairToken($len = 20)
  {
    $string = '';
    $pool   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    for ($i = 1; $i <= $len; $i++)
    {
      $string .= substr($pool, rand(0, 61), 1);
    }
    $token = $string;
    $enctoken = '{SHA}'.base64_encode (sha1($string,true));

    return array($token,$enctoken);
  }

}
