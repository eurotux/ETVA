<?php

class SquidCachePeerForm extends myBaseForm
{
  protected static $options_values = array('proxy-only','default',
                                           'ttl','no-query','closest-only','no-netdb-exchange',
                                            'round-robin','weight','no-digest','no-delay',
                                            'login','connect-timeout','allow-miss','htcp',
                                            'originserver','multicast-responder','digest-url',
                                            'max-conn','forceddomain','ssl');
  protected static $types = array('parent','sibling','multicast');
  protected static $peer_domain = array('index','dontquery','query');

  public function configure()
  {      
      
      $this->setValidators(array(
            'index' => new sfValidatorNumber(array('required' => false)),
            'hostname' => new sfValidatorRegex(
                            //array('pattern' =>'#^(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)$#'),
                            array('pattern' =>'#^[0-9a-zA-Z._\-]+$#'),
                            array('invalid'=>'"%value%" is not valid field.')),
            'http-port' => new sfValidatorNumber(array('required' => true)),
            'icp-port' => new sfValidatorNumber(array('required' => true)),
            'type' => new sfValidatorChoice(array('choices'=>self::$types)),
            'options'    => new ValidatorArray(array('required' => false,'assoc'=>true,'choices' => self::$options_values)),
            'cache_peer_domain' => new sfCachePeerDomainValidator(array('choices' => self::$peer_domain))
          ));
      
  }
}
