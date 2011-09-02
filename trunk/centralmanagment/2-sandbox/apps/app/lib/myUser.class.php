<?php

class myUser extends sfGuardSecurityUser
{
    public function getLastLogin()
    {
        return $this->getGuardUser()->getLastLogin();
    }

    public function getId()
    {
        return $this->getGuardUser()->getId();
    }

    public function initVncToken()
    {

        $user_id = $this->getGuardUser()->getId();
        $user_name = $this->getUsername();

        $vncToken = EtvaVncTokenPeer::retrieveByPK($user_name);
        if(!$vncToken) $vncToken = new EtvaVncToken();

        // generate new data

        $tokens = self::generatePairToken();

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


    /*
     * function to check/set if is first time request
     */
    public function isFirstRequest($boolean = null)
    {
        if (is_null($boolean))
        {
            return $this->getAttribute('first_request', true);
        }

        $this->setAttribute('first_request', $boolean);
    }
    

}
