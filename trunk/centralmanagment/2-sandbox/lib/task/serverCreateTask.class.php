<?php

class serverCreateTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('name', sfCommandArgument::REQUIRED, 'Server name'),
      new sfCommandArgument('vm_type', sfCommandArgument::REQUIRED, 'Server type'),
      new sfCommandArgument('vm_os', sfCommandArgument::REQUIRED, 'Server os'),
      new sfCommandArgument('mem', sfCommandArgument::REQUIRED, 'Server memory'),
      new sfCommandArgument('vcpu', sfCommandArgument::REQUIRED, 'Server cpus'),
      new sfCommandArgument('boot', sfCommandArgument::REQUIRED, 'Server boot option'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
      new sfCommandOption('node', null, sfCommandOption::PARAMETER_REQUIRED, 'The node where server should be created'),

      new sfCommandOption('disks', null, sfCommandOption::PARAMETER_OPTIONAL | sfCommandOption::IS_ARRAY, 'The disks of server'),
      new sfCommandOption('networks', null, sfCommandOption::PARAMETER_OPTIONAL | sfCommandOption::IS_ARRAY, 'The networks of server'),

      new sfCommandOption('location', null, sfCommandOption::PARAMETER_OPTIONAL, 'The location of installation of server'),
      new sfCommandOption('ip', null, sfCommandOption::PARAMETER_OPTIONAL, 'The IP address of server'),
      new sfCommandOption('description', null, sfCommandOption::PARAMETER_OPTIONAL, 'The description of server'),
    ));

    $this->namespace        = 'server';
    $this->name             = 'create';
    $this->briefDescription = 'Create new server';
    $this->detailedDescription = <<<EOF
The [server:create|INFO] task does things.
Call it with:

  [php symfony server:create|INFO]
EOF;
  }

  /**
    * Check the given url for avaiability
    */
  private function validateLocationUrl($url)
  {
    $url_obj = parse_url($url);
    $valid = false;

    if($url_obj['scheme'] == 'ftp'){
      $valid = $this->checkFtpDir($url);                
    }else if(preg_match('/^(http|https)$/', $url_obj['scheme']) && get_headers($url)){
      $valid = true;
    }else if($url_obj['scheme'] == 'nfs'){
      $valid = true;
    }
    return $valid;
  }
  private function checkFtpDir($url)
  {
    $url_obj = parse_url($url);

    // set up basic connection
    $conn_id = ftp_connect($url_obj['host']); 

    // login with username and password
    $login_result = ftp_login($conn_id, 'anonymous', ''); 

    // check connection
    if ((!$conn_id) || (!$login_result)) { 
      error_log("[INFO] FTP connection has failed!");
      ftp_close($conn_id);
      return FALSE;
    }

    // Retrieve directory listing
    $files = ftp_nlist($conn_id, $url_obj['path']);
    if($files == FALSE){
      ftp_close($conn_id);
      return FALSE;
    }

    // close the FTP stream 
    ftp_close($conn_id);
    return TRUE;
  }

  private function prepare_disks($disks)
  {
    $disks_aux = array();
    foreach($disks as $disk)
    {
        if( $disk['from_task_id'] )
        {
            if( $jobObj = EtvaAsynchronousJobPeer::retrieveByPK($disk['from_task_id']) )
            {
                if( $res_str = $jobObj->getResult() ){
                    $result = json_decode($res_str,true);
                    if( $result && $result['success'] ){
                        $disk['id'] = $result['insert_id'];
                        $disk['uuid'] = $result['uuid'];
                    }
                }
            }
        }
        array_push($disks_aux, $disk);
    }
    return $disks_aux;
  }

  // aux func for process disks and networks as array
  protected function process_array_of_arguments($args)
  {
    $args_arr = array();
    if( is_string($args) )
    {
        $args_arr = json_decode($args,true);
    }
    else if( is_array($args) )
    {
        foreach($args as $arg)
        {
            if( is_string($arg) )
            {
                $arg_arr = json_decode($arg,true);
                if( !$arg_arr )     // when json decode fail
                {
                    $arg_arr = array();
                    $arg = preg_replace('/{(.+?)}/','\1',$arg); // clean { }
                    $arg_arr_aux = explode(',', $arg);
                    foreach($arg_arr_aux as $arg_str)
                    {
                        $arg_args = explode(':',$arg_str,2);
                        $key = $arg_args[0];
                        $val = $arg_args[1];
                        $arg_arr[$key] = $val;
                    }
                }
                array_push($args_arr,$arg_arr);
            } else {
                array_push($args_arr,$arg);
            }
        }
    }
    return $args_arr;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // Context
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $node = $options['node'];

    $etva_node = EtvaNodePeer::retrieveByPK($node);
    if(!$etva_node)
    {
        $msg_i18n = $context->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$node));
        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        return $error;
    }

    if($arguments['boot'] == 'location'){
        $valid = $this->validateLocationUrl($options['location']);
        if($valid == false){
            $msg_i18n = $context->getI18N()->__('Could not validate the location URL!');
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
            return $error;
        }
    }

    $server = array_merge($arguments,$options);

    // decode networks
    if( $server['networks'] )
    {
        $server['networks'] = $this->process_array_of_arguments($server['networks']);
    }
    //$this->log("[DEBUG] server networks ".print_r($server['networks'],true));

    // decode disks
    if( $server['disks'] )
    {
        $server_disks = $this->process_array_of_arguments($server['disks']);
        $server['disks'] = $this->prepare_disks($server_disks);
    }
    //$this->log("[DEBUG] server disks ".print_r($server['disks'],true));

    $etva_server = new EtvaServer();        
    $server_va = new EtvaServer_VA($etva_server);
    
    $response = $server_va->send_create($etva_node,$server);

    if($response['success']){
        $this->log("[INFO] ".$response['response']);
    } else {
        $this->log("[ERROR] ".$response['error']);
    }

    return $response;
  }
}
