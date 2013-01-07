<?php

/**
 * setting actions.
 *
 * @package    centralM
 * @subpackage setting
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z fabien $
 */
class settingActions extends sfActions
{
  public function executeView(sfWebRequest $request)
  {
      $etva_data = Etva::getEtvaModelFile();
      $this->etvamodel = $etva_data['model'];      
      $this->host = $request->getHost();
  }      
    
    public function executeJsonShutdown(sfWebRequest $request)
    {
        error_log("[INFO] shutdown Central MANAGEMENT");
        
        $command = "poweroff";
        error_log('[COMMAND]'.$command);

        $path = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR."utils";
        error_log("[INFO] PATH TO SUDOEXEC".$path.DIRECTORY_SEPARATOR);
        ob_start();
        passthru('echo '.$command.' | sudo /usr/bin/php -f '.$path.DIRECTORY_SEPARATOR.'sudoexec.php',$return);                
        ob_end_clean();
        error_log("[INFO] Shutdown executed.");
        error_log("[INFO] ".$return);

        if($return != 0){
            $msg = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>implode('<br>',$remote_errors),'error'=>implode(' ',$remote_errors));
            $error = $this->setJsonError($msg);
            return $this->renderText($error);
        }else{
            
            $msg =  array('success'=>true);
            return $this->renderText(json_encode($msg));
        }
    }

  public function executeJsonSetting(sfWebRequest $request)
  {
      $method = $request->getParameter('method');

      switch($method){          
          case 'update':
              $this->forward('setting', 'jsonUpdateSetting');
              break;
          default:
              $data = array();
              $params = $request->getParameter('params');
              $params_decoded = json_decode($params);

              //check if system network is to be presented
              $net_index = array_search('networks',$params_decoded);
              if($net_index!==false){
                  unset($params_decoded[$net_index]);
              }
                            
              foreach($params_decoded as $param)
              {
                  $etva_setting = EtvaSettingPeer::retrieveByPk($param);

                  if(!$etva_setting){
                    $msg =  array('success'=>false,'data'=>array());
                    return $this->renderText(json_encode($msg));
                  }

                  $data[$etva_setting->getParam()] = $etva_setting->getValue();

                  
              }

              if($net_index!==false){
                  $etva_data = Etva::getEtvaModelFile();
                  $etvamodel = $etva_data['model'];                  
                  
                  /*
                   *
                   * get system network connectivity
                   *
                   */
                  $cm_networks = new SystemNetworkUtil();

                  $interfaces_devices = sfConfig::get('app_device_interfaces');
                  $devices = $interfaces_devices[$etvamodel];

                  $cm_networks->loadCMNetworks($devices);                                                     

                  $networks = $cm_networks->getNetworks();
                                    
                  foreach($devices as $type=>$intf){
                      $netdata = $networks[$intf];
                      if( $netdata ){
                          $static = 0;
                          $bootp = $netdata->get(SystemNetwork::BOOTP);
                          if(strtolower($bootp)=='none' || strtolower($bootp)=='static' ) $static = 1;

                          $ip = $netdata->get(SystemNetwork::IP);
                          $subnet = $netdata->get(SystemNetwork::NETMASK);
                          $gw = $netdata->get(SystemNetwork::GW);
                          $if = $netdata->get(SystemNetwork::INTF);
                          $dns = $netdata->get(SystemNetwork::DNS);
                          $seconddns = $netdata->get(SystemNetwork::DNSSec);

                          $data['network_'.$type.'_static'] = $static;
                          $data['network_'.$type.'_bootp'] = $static ? 'static' : 'dhcp';
                          $data['network_'.$type.'_ip'] = $ip;
                          $data['network_'.$type.'_netmask'] = $subnet;
                          $data['network_'.$type.'_gateway'] = $gw;
                          $data['network_'.$type.'_if'] = $if;
                          $data['network_primarydns'] = $dns;
                          $data['network_secondarydns'] = $seconddns;
                          $data['network_staticdns'] = $static;
                          $data['network_bootpdns'] = $static ? 'static' : 'dhcp';
                      }
                  }
              }
       
              $msg =  array('success'=>true,'data'=>$data);

              return $this->renderText(json_encode($msg));
              break;
      }

  }

  /**
   * Perform update on table setting
   *
   * @param      string $param parameter name to perform operation
   * @param      string $value value for the $param
   *
   */
  public function executeJsonUpdateSetting(sfWebRequest $request)
  {

        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');
        

        $settings = $request->getParameter('settings');
        $settings_decoded = json_decode($settings,true);
        $force = $request->getParameter('force');        
        
        $networks = $request->getParameter('networks');
        $network_decoded = json_decode($networks,true);

        if($networks)
        {

            $etva_data = Etva::getEtvaModelFile();
            $etvamodel = $etva_data['model'];



            /*
             * get nodes....and call node agent script
             */
            $remote_errors = array();
            $change_networks = 0;
            $cm_networks = new SystemNetworkUtil();

            $interfaces_devices = sfConfig::get('app_device_interfaces');
            $devices = $interfaces_devices[$etvamodel];

            $cm_networks->loadCMNetworks($devices); 
            $sys_networks = $cm_networks->getNetworks();

            if($etvamodel=='standard'){
                $local = $network_decoded['lan'];
                $remote = $network_decoded['cm_management'];
                $cm_ip = $remote['ip'];

                $local_net = new SystemNetwork();
                $local_net->fromArray($local);

                $remote_net = new SystemNetwork();
                $remote_net->fromArray($remote);

                $sys_lan_if = $devices['lan'];
                $sys_manag_if = $devices['cm_management'];
                $sys_lan_net = $sys_networks[$sys_lan_if];
                $sys_manag_net = $sys_networks[$sys_manag_if];

                if(!$local_net->equals($sys_lan_net) || !$remote_net->equals($sys_manag_net)) $change_networks = 1;

            }
            else{
                $local = $network_decoded['cm_management'];
                $remote = array();
                $cm_ip = $local['ip'];

                $local_net = new SystemNetwork();
                $local_net->fromArray($local);
                $sys_manag_if = $devices['cm_management'];
                $sys_manag_net = $sys_networks[$sys_manag_if];

                if(!$local_net->equals($sys_manag_net)) $change_networks = 1;

            }

            if($change_networks) //changed networks send info to nodes...
            {

                $criteria = new Criteria();
                $etva_nodes = EtvaNodePeer::doSelect($criteria);

                if(!$force)
                {// check for nodes state if not force
                    foreach($etva_nodes as $etva_node)
                    {

                        $node_state = $etva_node->getState();

                        if(!$node_state)
                            $remote_errors[] = Etva::getLogMessage(array('agent'=>$etva_node->getName(),'msg'=>'Down'), Etva::_AGENT_MSG_);

                        /*
                         * check if servers has an MA and are down...send error
                         */
                        $etva_servers = $etva_node->getEtvaServers();
                        foreach($etva_servers as $etva_server)
                        {
                            $server_ma = $etva_server->getAgentTmpl();
                            $server_state = $etva_server->getState();

                            if(!$server_state && $server_ma)
                                $remote_errors[] = Etva::getLogMessage(array('agent'=>$etva_server->getName(),'msg'=>'Down'), Etva::_AGENT_MSG_);
                        }


                    }//end foreach
                }

                if($remote_errors && !$force){

                    $msg = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'action'=>'check_nodes','info'=>implode('<br>',$remote_errors),'error'=>implode(' ',$remote_errors));
                    $error = $this->setJsonError($msg);
                    return $this->renderText($error);

                }


                if($etvamodel!='standard'){ // only if not standard version
                
                    /*
                     * 
                     * check ISO DIR in use
                     *
                     */
                    $isosdir = sfConfig::get("config_isos_dir");
                    $criteria = new Criteria();
                    $criteria->add(EtvaServerPeer::LOCATION, "%${isosdir}%",Criteria::LIKE);                
                   
                    $criteria->add(EtvaServerPeer::VM_STATE,'running');

                    $servers_running_iso = EtvaServerPeer::doSelect($criteria);
                    

                    foreach($servers_running_iso as $server)
                    {
                        
                        $remote_errors[] = $this->getContext()->getI18N()->__(EtvaServerPeer::_CDROM_INUSE_,array('%name%'=>$server->getName()));
                    }

                    if($remote_errors){

                        $message = Etva::getLogMessage(array('info'=>ETVA::_CDROM_INUSE_), ETVA::_ERR_ISODIR_INUSE_);
                        $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                        $i18n_br_sep = implode('<br>',$remote_errors);
                        $i18n_sp_sep = implode(' ',$remote_errors);

                        //$iso_br_msg = Etva::getLogMessage(array('info'=>'<br>'.$br_sep), ETVA::_ERR_ISODIR_INUSE_);
                        $i18n_iso_br_msg = $this->getContext()->getI18N()->__(ETVA::_ERR_ISODIR_INUSE_,array('%info%'=>'<br>'.$i18n_br_sep));
                        //$iso_sp_msg = Etva::getLogMessage(array('info'=>$sp_sep), ETVA::_ERR_ISODIR_INUSE_);
                        $i18n_iso_sp_msg = $this->getContext()->getI18N()->__(ETVA::_ERR_ISODIR_INUSE_,array('%info%'=>$i18n_sp_sep));

                        $msg = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$i18n_iso_br_msg,'error'=>$i18n_iso_sp_msg);
                        $error = $this->setJsonError($msg);
                        return $this->renderText($error);
                    }                

                    /*
                     * if all ok so far.....send nodes umount ISO DIR
                     */
                    foreach($etva_nodes as $etva_node)
                    {
                        $node_va = new EtvaNode_VA($etva_node);

                        $response = array('success'=>true);

                        if($force && $etva_node->getState()) $response = $node_va->send_umount_isosdir();

                        $success = $response['success'];
                        if(!$success)
                        {                        
                            $node_msg = Etva::getLogMessage(array('name'=>$response['agent'],'info'=>$response['error']), EtvaNodePeer::_ERR_ISODIR_UMOUNT_);

                            $remote_errors[] = $node_msg;

                            $message = Etva::getLogMessage(array('info'=>$node_msg), EtvaSettingPeer::_ERR_SETTING_REMOTE_CONNECTIVITY_SAVE_);
                            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));                        
                        }
                        
                    }

                    if(!empty($remote_errors)){
                        $msg = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>implode('<br>',$remote_errors),'error'=>implode(' ',$remote_errors));
                        $error = $this->setJsonError($msg);
                        return $this->renderText($error);
                    }
                }
                

                /*
                 * update using local script
                 */

                $local_updated = $this->localUpdate($local);
                if(!$local_updated){


                    $intf = $local['if'];
                    $msg_i18n = $this->getContext()->getI18N()->__(SystemNetwork::_ERR_NOTFOUND_INTF_,array('%name%'=>$intf,'%info%'=>''));
                    $info = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n);
                    $error = $this->setJsonError($info);

                    return $this->renderText($error);
                }

                if(empty($remote_errors))
                {

                    foreach($etva_nodes as $etva_node)
                    {
                        // send update to nodes if force

                        $etva_node->setSoapTimeout(5);
                        $remote_updated = $this->remoteUpdate($etva_node,$remote,$cm_ip);


                        if($remote_updated !== true){

                            if($remote_updated === false){
                                $intf = $remote['intf'];
                                $msg_i18n = $this->getContext()->getI18N()->__(SystemNetwork::_ERR_NOTFOUND_INTF_,array('%name%'=>$intf,'%info%'=>''));
                                $remote_errors[] = $msg_i18n;
                                //$error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n);

                            }else{

                                $agent_msg = Etva::getLogMessage(array('agent'=>$remote_updated['agent'],'msg'=>$remote_updated['error']), Etva::_AGENT_MSG_);
                                $message = Etva::getLogMessage(array('info'=>$agent_msg), EtvaSettingPeer::_ERR_SETTING_REMOTE_CONNECTIVITY_SAVE_);
                                $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
                                $remote_errors[] = $agent_msg;
                            }
                        }


                        /*
                         * update MA if any...
                         *
                         */
                        $etva_servers = $etva_node->getEtvaServers();
                        foreach($etva_servers as $etva_server)
                        {

                            $server_ma = $etva_server->getAgentTmpl();
                            $server_state = $etva_server->getState();

                            if($server_state && $server_ma){

                                $aux_ip = $etva_server->getIp();
                                $etva_server->setSoapTimeout(5);
                                $remote_updated = $this->remoteUpdate($etva_server,$remote,$cm_ip);

                                if($remote_updated === false){
                                    $intf = $remote['intf'];
                                    $msg_i18n = $this->getContext()->getI18N()->__(SystemNetwork::_ERR_NOTFOUND_INTF_,array('%name%'=>$intf,'%info%'=>''));
                                    $remote_errors[] = $msg_i18n;
                                    //$error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n);

                                }else{

                                    $agent_msg = Etva::getLogMessage(array('agent'=>$remote_updated['agent'],'msg'=>$remote_updated['error']), Etva::_AGENT_MSG_);
                                    $message = Etva::getLogMessage(array('info'=>$agent_msg), EtvaSettingPeer::_ERR_SETTING_REMOTE_CONNECTIVITY_SAVE_);
                                    $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
                                    $remote_errors[] = $agent_msg;
                                }
                            }
                            
                        }

                    }
                }

                if(!empty($remote_errors)){
                    $msg = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>implode('<br>',$remote_errors),'error'=>implode(' ',$remote_errors));
                    $error = $this->setJsonError($msg);
                    return $this->renderText($error);
                }

                

            }// end if changed networks
        }

        foreach($settings_decoded as $data){
            $param = $data['param'];
            $value = $data['value'];

            if(!$etva_setting = EtvaSettingPeer::retrieveByPk($param)){
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaSettingPeer::_ERR_NOTFOUND_PARAM_,array('%id%'=>$param));
                $info = array('success'=>false,'error'=>$msg_i18n);
                $error = $this->setJsonError($info);
                return $this->renderText($error);
            }

            $etva_setting->setValue($value);

            switch($param){
                case 'vnc_keymap' : if($etva_setting->saveVNCkeymap()){
                                        //notify system log
                                        $this->dispatcher->notify(
                                            new sfEvent(sfConfig::get('config_acronym'),
                                                    'event.log',
                                                     array('message' => Etva::getLogMessage(array('name'=>$etva_setting->getValue()), EtvaSettingPeer::_OK_VNCKEYMAP_CHANGE_))
                                        ));

                                    }else{
                                        //notify system log
                                        $this->dispatcher->notify(
                                            new sfEvent(sfConfig::get('config_acronym'),
                                                    'event.log',
                                                    array('message' => Etva::getLogMessage(array('name'=>$value), EtvaSettingPeer::_ERR_VNCKEYMAP_CHANGE_),'priority'=>EtvaEventLogger::ERR)));
                                    }
                                    break;
                default:
                                    try{
                                        $etva_setting->save();
                                        //notify system log
                                        $this->dispatcher->notify(
                                            new sfEvent(sfConfig::get('config_acronym'),
                                                    'event.log',
                                                     array('message' => Etva::getLogMessage(array('name'=>$etva_setting->getParam(),'value'=>$etva_setting->getValue()), EtvaSettingPeer::_OK_SETTING_CHANGE_))
                                        ));
                                    }catch(Exception $e){
                                        //notify system log
                                        $this->dispatcher->notify(
                                            new sfEvent(sfConfig::get('config_acronym'),
                                                    'event.log',
                                                    array('message' => Etva::getLogMessage(array('name'=>$value), EtvaSettingPeer::_ERR_SETTING_CHANGE_),'priority'=>EtvaEventLogger::ERR)));
                                    }
                                    break;
            }

        }// end foreach              


        $msg_i18n = $this->getContext()->getI18N()->__(EtvaSettingPeer::_OK_SETTING_CONNECTIVITY_SAVE_);
        $message = EtvaSettingPeer::_OK_SETTING_CONNECTIVITY_SAVE_;
        $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message)));

        $info = array('success'=>true,'agent'=>sfConfig::get('config_acronym'),'response'=>$msg_i18n);
        return $this->renderText(json_encode($info));

  }

  /*
   * update node interface. send remote call
   */
  private function remoteUpdate($etva_node,$net,$cm_uri_ip = false, $cm_uri_port = false)
  {
      
      $sys = new SystemNetwork();
      if(!$sys->fromArray($net)) return false;
      
      if($cm_uri_ip) $sys->setURI($cm_uri_ip,$cm_uri_port);

      $method = 'change_ip';
      $params = $sys->_VA();        

      $response = $etva_node->soapSend($method,$params);      
      $success = $response['success'];

      if(!$success){

          /*
           * if error is caused by socket read timeout then maybe ip changed
           */
          if($response['faultactor']=='socket_read'){
                //return true;
                //$etva_node->setIp($sys->get($sys->getIntf(), SystemNetwork::IP));
                //$etva_node->save();
          }else{
              $info = array('success'=>false,'agent'=>$response['agent'],'error'=>$response['info'],'info'=>$response['info']);
              return $info;

          }

      }
      return true;
  }

  /*
   * call local script change interface
   */
  private function localUpdate($net)
  {
      $sys = new SystemNetwork();
      
      if(!$sys->fromArray($net)) return false;      
        
      $updated_network = SystemNetworkUtil::updateLocalNetwork($sys);      
      
      if(!$updated_network) return false;

      return true;
  }

  protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

  }


}
