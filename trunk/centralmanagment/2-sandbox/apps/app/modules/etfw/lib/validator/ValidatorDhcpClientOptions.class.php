<?php

class ValidatorDhcpClientOptions extends sfValidatorBase
{
  
  private static $prefix = 'option';

  private $errors = array();

  private static $cli_options = array('host-name'=>'host-name',
                                      'subnet-mask'=>'subnet-mask',
                                      'broadcast-address'=>'broadcast-address',
                                      'domain-name'=>'domain-name',
                                      'domain-name-servers'=>'domain-name-servers',
                                      'netbios-scope'=>'netbios-scope',
                                      'time-offset'=>'time-offset',
                                      'slp-directory-agent'=>'slp-directory-agent',
                                      'slp-service-scope'=>'slp-service-scope',
                                      'routers'=>'routers',
                                      'time-servers'=>'time-servers',
                                      'log-servers'=>'log-servers',
                                      'swap-server'=>'swap-server',
                                      'root-path'=>'root-path',
                                      'nis-domain'=>'nis-domain',
                                      'nis-servers'=>'nis-servers',
                                      'font-servers'=>'font-servers',
                                      'x-display-manager'=>'x-display-manager',
                                      'static-routes'=>'static-routes',
                                      'ntp-servers'=>'ntp-servers',
                                      'netbios-name-servers'=>'netbios-name-servers',
                                      'netbios-node-type'=>'netbios-node-type',
                                      'time-offset'=>'time-offset',
                                      'dhcp-server-identifier'=>'dhcp-server-identifier');
  
  protected function configure($options = array(), $messages = array())
  {    

    $this->setMessage('invalid', '"%value%" is not valid field.');
    $this->addMessage('invalid_format', '"%value%" is not in valid format.');
    $this->addMessage('type_error', 'Wrong type.');
  }

  /**
   *
   *
   * @see sfValidatorBase
   */
  protected function doClean($data)
  {      

      if (!is_array($data))
      {
          throw new sfValidatorError($this, 'type_error');
      }

      foreach ($data as $k=>$v)
      {
          if(!in_array($k,self::$cli_options))
          {
              throw new sfValidatorError($this, 'invalid', array('value' => $k));
          }
      }
      
      //host name
      $this->validate('ValidatorString', $data, 'host-name');

      //routers
      $this->getAndValidateIps($data,'routers');
      
      //subnet mask
      $this->validate('ValidatorIP', $data, 'subnet-mask');

      //broadcast address
      $this->validate('ValidatorIP', $data, 'broadcast-address');

      //domain-name
      $this->validate('ValidatorString', $data, 'domain-name');
      
      //dns
      $this->getAndValidateIps($data,'domain-name-servers');
    
      //time servers
      $this->getAndValidateIps($data,'time-servers');

      //log servers
      $this->getAndValidateIps($data,'log-servers');
      
      //swap server
      $this->validate('ValidatorIP', $data, 'swap-server');

      //root path
      $this->validate('ValidatorString', $data, 'root-path');

      //nis domain
      $this->validate('ValidatorString', $data, 'nis-domain');
          
      //nis servers
      $this->getAndValidateIps($data,'nis-servers');

      //font servers
      $this->getAndValidateIps($data,'font-servers');
          
      //xdm servers
      $this->getAndValidateIps($data,'x-display-manager');
      
      //static routes
      $this->validateIpsPairs($data,'static-routes');
      
      //ntp servers
      $this->getAndValidateIps($data,'ntp-servers');
      
      //netbios name servers
      $this->getAndValidateIps($data,'netbios-name-servers');
    
      //netbios scope
      $this->validate('ValidatorString', $data, 'netbios-scope');

      //netbios node type
      $this->validate('ValidatorNumber', $data, 'netbios-node-type');

      //time offset
      $this->validate('ValidatorNumber', $data, 'time-offset');

      //dhcp server identifier
      $this->validate('ValidatorIP', $data, 'dhcp-server-identifier');

      //slp directory agent
      $slp_directory = $this->getValue($data, 'slp-directory-agent');
      if($slp_directory){
          $hasTrueFalse = preg_match('/^(true )|^(false )/',$slp_directory);
          if($hasTrueFalse)
          {
              $slp_directory_ips = preg_replace('/^(true )|^(false )/','',$slp_directory);
              $this->validateIps('slp-directory-agent', $slp_directory_ips);
              
          }
          else{
              $this->addError('slp-directory-agent', new sfValidatorError($this, 'invalid', array('value' => $slp_directory)));
          }                

      }

      //slp service scope
      $slp_scope = $this->getValue($data, 'slp-service-scope');
      if($slp_scope){
          $hasTrueFalse = preg_match('/^(true )|^(false )/',$slp_scope);
          if($hasTrueFalse)
          {
              $slp_scope_name = preg_replace('/^(true )|^(false )/','',$slp_scope);
              $validate = new ValidatorString();
              $validate->validate($slp_scope_name);
              $this->addError('slp-service-scope', $validate->getError());                            

          }
          else{
              $this->addError('slp-service-scope', new sfValidatorError($this, 'invalid', array('value' => $slp_scope)));
          }         

      }
  
      
      if($this->errors) throw new sfValidatorErrorSchema($this, $this->errors);

      return $data;
  }

  /*
   * format for validation: pair x.x.x.x y.y.y.y comma separated
   * Example:
   *     "x.x.x.x y.y.y.y,z.z.z.z,d.d.d.d"
   */
  private function isPair($data_string)
  {

      $data_split = explode(",",$data_string);
      foreach($data_split as $pair){
        $check_pair = preg_split("/\s+/",$pair);
        if(count($check_pair)!=2) return false;
      }

      return $check_pair;
  }


  private function getAndValidateIps($data,$field)
  {
      $ips_string = $this->getValue($data, $field);
      $this->validateIps($field,$ips_string);
  }

  /*
   * validate ips in string format: 'x.x.x.x, y.y.y.y, z.z.z.z'
   */
  private function validateIps($field,$data)
  {      
      
      $ips = preg_replace('/\s+/',' ',$data);
      $ips_split = explode(', ',$ips);            
      $validate_ip = new ValidatorIP();
      foreach($ips_split as $ip){          
          $validate_ip->validate($ip);
      }
      $this->addError($field, $validate_ip->getError());

  }


  /*
   * validate ips in string format: 'x.x.x.x y.y.y.y, z.z.z.z w.w.w.w'
   */
  private function validateIpsPairs($data,$field)
  {
      $ips_string = $this->getValue($data, $field);

      $ips = preg_replace('/\s+/',' ',$ips_string);
      $ips_split = explode(', ',$ips);
      
      foreach($ips_split as $ip){

          if($ips = $this->isPair($ip))
          {
              $validate_ip1 = new ValidatorIP();
              $validate_ip1->validate($ips[0]);
              $this->addError($field, $validate_ip1->getError());
              
              $validate_ip2 = new ValidatorIP();
              $validate_ip2->validate($ips[1]);
              $this->addError($field, $validate_ip2->getError());
              
          }else{
              $this->addError($field, new sfValidatorError($this, 'invalid', array('value' => $ip)));
          }
            
      }
      

  }

  /*
   * return value if data cli_options index exists otherwise return empty
   */
  private function getValue($data,$field)
  {
      $value = isset($data[self::$cli_options[$field]]) ? $data[self::$cli_options[$field]] : '';      
      return $value;      
  }

  /*
   * try to validate
   * 
   * @param validator class $class
   * @param array $data     An array with all the options data
   * @param       $field     Form field name to validate
   *
   */
  private function validate($class, $data, $field)
  {
      $value = $this->getValue($data, $field);
      $validate_class = new $class();
      $validate_class->validate($value);
      $this->addError($field, $validate_class->getError());
  }

  private function addError($field, $err)
  {
      if($err) $this->errors[self::$prefix.'_'.$field] = $err;
  }
  


}
