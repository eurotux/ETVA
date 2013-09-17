<?php
/**
 * ovf actions.
 *
 * @package    centralM
 * @subpackage ovf
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
class ovfActions extends sfActions
{

    
    /**
     * shows ovf import wizard template
     *
     */
    public function executeOvfImport()
    {
    }

    public function executeOvfExport()
    {
    }

    /**
     * 
     * exports virtual machine in ovf format
     * 
     */
    public function executeOvfDownload(sfWebRequest $request)
    {
        //$this->getUser()->shutdown();
        //session_write_close();

        if( $sid = $request->getParameter('uuid') ){
            $etva_server = EtvaServerPeer::retrieveByUuid($sid);
        } else {
            $sid = $request->getParameter('sid');
            $etva_server = EtvaServerPeer::retrieveByPK($sid);
        }

        if(!$etva_server){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));            
            return $this->renderText($msg_i18n);
        }

        $snapshot = $request->getParameter('snapshot');

        if(!$snapshot && ($etva_server->getVmState() != 'stop') && ($etva_server->getVmState() != 'notrunning') ){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));            
            return $this->renderText($msg_i18n);
        }
        

        $etva_node = $etva_server->getEtvaNode();
        
        $url = "http://".$etva_node->getIp();
        $request_body = "uuid=".$etva_server->getUuid();

        if( $snapshot ){
            $request_body .= "&snapshot=$snapshot";
        }

        $filename = $etva_server->getName().".tar";
        
        $port = $etva_node->getPort();
        if($port) $url.=":".$port;        
        $url.="/vm_ovf_export_may_fork";
        
        /*
         * get response stream data
         */
        $ovf_curl = new ovfcURL($url);
        $ovf_curl->post($request_body);
        $ovf_curl->setFilename($filename);
        $ovf_curl->exec();

        if($ovf_curl->getStatus()==500) return $this->renderText('Error 500');

        return sfView::NONE;
    }


    public function executeJsonLoadDescriptor(sfWebRequest $request)
    {        
        // remove session macs for cleanup the wizard
        $this->getUser()->getAttributeHolder()->remove('macs_in_wizard');        

        $url = $request->getParameter('ovf_location_url');        

        $env = new OvfEnvelope();        
        $imported = $env->ovfImport($url);

        if(!$imported){
            $parse_err = $this->getContext()->getI18N()->__(OvfEnvelope_VA::_ERR_PARSING_,array('%url%'=>$url));
            $msg_ = $this->getContext()->getI18N()->__(OvfEnvelope_VA::_ERR_IMPORT_,array('%info%'=>$parse_err));
            $error = $this->setJsonError(array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_,'info'=>$msg_));
            return $this->renderText($error);            
        }

        $vs = $env->getVirtualSystem();

        /*
         * ovf details
         */
        $refs = $env->getReferences();
        $ps = $vs->getProductSection();
        $ps_array = $ps->toArray();

        $ovf_details = $ps_array;
        $ovf_details['ovf_size'] = $refs->getTotalSize();

        /*
         * ovf eula
         */

        $eula = $vs->getEulaSection();
        $eula_array = $eula->toArray();

        $ovf_eula = $eula_array;

        /*
         * ovf name
         */
        $vs_array = $vs->toArray();
        $ovf_name = $vs_array;

        $memory = $env->getMemory();
        $ovf_name['memory'] = $memory;

        /*
         * ovf storage
         */
        $disks_array = $env->getDisks();                
        $ovf_storage = $disks_array;        


        /*
         * ovf networks
         */
        $networks_array = $env->getNetworks();
        $total_networks = count($networks_array);
        $ovf_networks = array();

        $action = $this->getController()->getAction('mac','generateUnused');
        foreach($networks_array as $if){            
            $result_ = $action->executeGenerateUnused();
            if($result_ === false){

                $msg_i18n = $this->getContext()->getI18N()->__(EtvaMacPeer::_ERR_AT_LEAST_MACS_,array('%num%'=>$total_networks));

                // if is browser request return text renderer
                $error = $this->setJsonError(array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n));
                return $this->renderText($error);
            }            

            $ovf_networks[] = array_merge($result_,$if);
        }        
        
        $result = array('success'   => true,
                        'data'      => array(
                                            'ovf_details'   => $ovf_details,
                                            'ovf_eula'      => $ovf_eula,
                                            'ovf_name'      => $ovf_name,
                                            'ovf_storage'   => $ovf_storage,                                            
                                            'ovf_networks'  => $ovf_networks));
        
        $return = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        // if is browser request return text renderer
        return  $this->renderText($return);

    }

    private function jsonImportCheck(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $import_data = json_decode($request->getParameter('import'),true);

        $server = $import_data;
        $server['name'] = $import_data['name'];
        $server['vm_type'] = $import_data['vm_type'];

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$node_log), OvfEnvelope_VA::_ERR_IMPORT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }


        /*
         * check if name is unique
         */
        if($etva_server = EtvaServerPeer::retrieveByName($server['name'])){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_EXIST_,array('%name%'=>$server['name']));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('name'=>$server['name']), EtvaServerPeer::_ERR_EXIST_);
            $message = Etva::getLogMessage(array('info'=>$server_log), OvfEnvelope_VA::_ERR_IMPORT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
            
        }

        if( isset($import_data['disks']) ){
            $disks = $import_data['disks'];
            foreach($disks as $id => $info){
                $vg = $info['vg'];
                $lv = $info['lv'];
                
                if($etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){

                    $msg_type = $is_DiskFile ? EtvaLogicalvolumePeer::_ERR_DISK_EXIST_ : EtvaLogicalvolumePeer::_ERR_LV_EXIST_;
                    $msg = Etva::getLogMessage(array('name'=>$lv), $msg_type);
                    $msg_i18n = $this->getContext()->getI18N()->__($msg_type,array('%name%'=>$lv));


                    $error = array('success'=>false,
                               'agent'=>$etva_node->getName(),
                               'error'=>$msg_i18n,
                               'info'=>$msg_i18n);

                    //notify system log
                    $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), OvfEnvelope_VA::_ERR_IMPORT_);
                    $this->dispatcher->notify(
                        new sfEvent($error['agent'], 'event.log',
                            array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                    return $error;
                }

                if(!$etva_vg = $etva_node->retrieveVolumegroupByVg($vg)){

                    $msg = Etva::getLogMessage(array('name'=>$vg), EtvaVolumegroupPeer::_ERR_NOTFOUND_);
                    $msg_i18n = $this->getContext()->getI18N()->__(EtvaVolumegroupPeer::_ERR_NOTFOUND_,array('%name%'=>$vg));

                    $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n, 'info'=>$msg_i18n);

                    //notify system log
                    $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), OvfEnvelope_VA::_ERR_IMPORT_);
                    $this->dispatcher->notify(
                        new sfEvent($error['agent'], 'event.log',
                            array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                    return $error;
                }
            }
        }

        if( isset($import_data['networks']) ){
            $networks = $import_data['networks'];
            $networks_va = $import_data['networks'];

            // check if networks are available
            foreach ($networks as $network){

                $etva_vlan = EtvaVlanPeer::retrieveByPk($network['vlan_id']);
                $etva_mac = EtvaMacPeer::retrieveByMac($network['mac']);

                /*
                 * TODO improve this to add Mac Address to the pool
                 */

                if(!$etva_mac || !$etva_vlan){

                    $msg = Etva::getLogMessage(array(), EtvaNetworkPeer::_ERR_);
                    $msg_i18n = $this->getContext()->getI18N()->__(EtvaNetworkPeer::_ERR_,array());

                    if( !$etva_mac ){
                        $msg = Etva::getLogMessage(array('%mac%'=>$network['mac']), EtvaMacPeer::_ERR_INVALID_MAC_);
                        $msg_i18n = $this->getContext()->getI18N()->__(EtvaMacPeer::_ERR_INVALID_MAC_,array('%mac%'=>$network['mac']));
                    }

                    $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

                    //notify event log
                    $message = Etva::getLogMessage(array('name'=>$server['name'],'info'=>$msg), OvfEnvelope_VA::_ERR_IMPORT_);
                    $this->dispatcher->notify(
                        new sfEvent($error['agent'], 'event.log',
                            array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                    return $error;
                }

                if($etva_mac->getInUse()){

                    $msg = Etva::getLogMessage(array('%name%'=>$etva_mac->getMac()), EtvaMacPeer::_ERR_ASSIGNED_);
                    $msg_i18n = $this->getContext()->getI18N()->__(EtvaMacPeer::_ERR_ASSIGNED_,array('%name%'=>$etva_mac->getMac()));

                    $error = array('success'=>false,'agent'=>$etva_node->getName(),'info'=>$msg_i18n,'error'=>$msg_i18n);

                    //notify event log
                    $message = Etva::getLogMessage(array('name'=>$server['name'],'info'=>$msg), OvfEnvelope_VA::_ERR_IMPORT_);
                    $this->dispatcher->notify(
                        new sfEvent($error['agent'], 'event.log',
                            array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                    return $error;
                }
            }
        }
        
        $msg_i18n = $this->getContext()->getI18N()->__(OvfEnvelope_VA::_OK_OVF_IMPORT_VALIDATION_,array());
        $message = Etva::getLogMessage(array(), OvfEnvelope_VA::_OK_OVF_IMPORT_VALIDATION_);
        $this->dispatcher->notify(new sfEvent($etva_node->getName(), 'event.log', array('message' => $message)));

        $result = array('success'=>true,
                        'agent'=>$response['agent'],
                        'response'=>$msg_i18n);

        return $result;

    }
    public function executeJsonImportCheck(sfWebRequest $request)
    {
        $result = $this->jsonImportCheck($request);

        // if is a CLI soap request return json encoded data
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);

        // if is browser request return text renderer
        if( !$result['success'] ){
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        } else {
            $return = json_encode($result);
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);                               
        }
    }
    public function executeJsonImport(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $import_data = json_decode($request->getParameter('import'),true);

        $server = $import_data;
        $vnc_keymap = EtvaSettingPeer::retrieveByPk('vnc_keymap');
        $server['vnc_keymap'] = $vnc_keymap->getValue();
        $server['uuid'] = EtvaServerPeer::generateUUID();
        $server['name'] = $import_data['name'];
        $server['vm_type'] = $import_data['vm_type'];
        $server['ip'] = '000.000.000.000';
        $server['boot'] = 'filesystem';
        
        $import_data['uuid'] = $server['uuid'];
        $import_data['vnc_keymap'] = $server['vnc_keymap'];
        
        // import validation check
        $result = $this->jsonImportCheck($request);

        if( !$result['success'] ){
            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);

            // if is browser request return text renderer
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$node_log), OvfEnvelope_VA::_ERR_IMPORT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $disks = $import_data['disks'];
        $collVgs = array();
        foreach($disks as $id => $info){
            $vg = $info['vg'];
            $lv = $info['lv'];
            
            if($etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){

                $msg_type = $is_DiskFile ? EtvaLogicalvolumePeer::_ERR_DISK_EXIST_ : EtvaLogicalvolumePeer::_ERR_LV_EXIST_;
                $msg = Etva::getLogMessage(array('name'=>$lv), $msg_type);
                $msg_i18n = $this->getContext()->getI18N()->__($msg_type,array('%name%'=>$lv));


                $error = array('success'=>false,
                           'agent'=>$etva_node->getName(),
                           'error'=>$msg_i18n,
                           'info'=>$msg_i18n);

                //notify system log
                $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), OvfEnvelope_VA::_ERR_IMPORT_);
                $this->dispatcher->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));


                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }

            if(!$etva_vg = $etva_node->retrieveVolumegroupByVg($vg)){

                $msg = Etva::getLogMessage(array('name'=>$vg), EtvaVolumegroupPeer::_ERR_NOTFOUND_);
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaVolumegroupPeer::_ERR_NOTFOUND_,array('%name%'=>$vg));

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n, 'info'=>$msg_i18n);

                //notify system log
                $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), OvfEnvelope_VA::_ERR_IMPORT_);
                $this->dispatcher->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);


            }

            // fix lv path
            $is_DiskFile = ($vg == sfConfig::get('app_volgroup_disk_flag')) ? 1:0;
            $import_data['disks'][$id]['lv'] = $is_DiskFile ? $etva_node->getStoragedir().'/'.$lv : $lv;

            $collVgs[$vg] = $etva_vg;
        }


        $networks = $import_data['networks'];
        $networks_va = $import_data['networks'];
        $collNetworks = array();
        $i = 0;
        // check if networks are available
        foreach ($networks as $network){

            if( $etva_vlan = EtvaVlanPeer::retrieveByPk($network['vlan_id']) ){
                $import_data['networks'][$i]['network'] = $etva_vlan->getName();
                $import_data['networks'][$i]['macaddr'] = $network['mac'];
            }
            
            $etva_mac = EtvaMacPeer::retrieveByMac($network['mac']);

            /*
             * TODO improve this to add Mac Address to the pool
             */

            if(!$etva_mac || !$etva_vlan){

                $msg = Etva::getLogMessage(array(), EtvaNetworkPeer::_ERR_);
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaNetworkPeer::_ERR_,array());

                if( !$etva_mac ){
                    $msg = Etva::getLogMessage(array('%mac%'=>$network['mac']), EtvaMacPeer::_ERR_INVALID_MAC_);
                    $msg_i18n = $this->getContext()->getI18N()->__(EtvaMacPeer::_ERR_INVALID_MAC_,array('%mac%'=>$network['mac']));
                }

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$server['name'],'info'=>$msg), OvfEnvelope_VA::_ERR_IMPORT_);
                $this->dispatcher->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }

            if($etva_mac->getInUse()){

                $msg = Etva::getLogMessage(array('%name%'=>$etva_mac->getMac()), EtvaMacPeer::_ERR_ASSIGNED_);
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaMacPeer::_ERR_ASSIGNED_,array('%name%'=>$etva_mac->getMac()));

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'info'=>$msg_i18n,'error'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$server['name'],'info'=>$msg), OvfEnvelope_VA::_ERR_IMPORT_);
                $this->dispatcher->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }

            $etva_network = new EtvaNetwork();
            $etva_network->fromArray($network,BasePeer::TYPE_FIELDNAME);
            $collNetworks[] = $etva_network;
            $i++;
        }
        
        $env = new OvfEnvelope_VA();
        $env->fromArray($import_data);

        /* get server copy VA server representation */
        $params = $env->_VA();
        

        $method = 'vm_ovf_import_may_fork';

        $response = $etva_node->soapSend($method,$params);

        if(!$response['success']){

            $error_decoded = $response['error'];

            $result = $response;
            
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_CREATE_,array('%name%'=>$server['name'],'%info%'=>$error_decoded));
            $result['error'] = $msg_i18n;

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$server['name'],'info'=>$response['info']), EtvaServerPeer::_ERR_CREATE_);
            $this->dispatcher->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }


        $response_decoded = (array) $response['response'];
        $returned_object = (array) $response_decoded['_obj_'];

        $returned_lvs = (array) $returned_object['LVS'];        


        $collLvs = array();
        foreach($returned_lvs as $disk){


            $disk_array = (array) $disk;

            $vg_info = (array) $disk_array[EtvaLogicalvolume::VOLUMEGROUP_MAP];
            $vg = $vg_info[EtvaVolumegroup::VG_MAP];

            $fake_lv_response =  array('success'=>true,'response' =>array('_obj_'=>$disk_array));                        

            // create logical volume

            $etva_lv = new EtvaLogicalvolume();
            $etva_lv->setEtvaVolumegroup($collVgs[$vg]);
            
            $lv_va = new EtvaLogicalvolume_VA($etva_lv);
            $lv_response = $lv_va->processResponse($etva_node, $fake_lv_response, 'lvcreate');

            if(!$lv_response['success'])
            {                
                $return = $this->setJsonError($lv_response);
                return  $this->renderText($return);
            }

            $collLvs[] = $etva_lv;

        }
      


        $etva_server = new EtvaServer();        

        $etva_server->fromArray($server,BasePeer::TYPE_FIELDNAME);

        $user_groups = $this->getUser()->getGroups();

        $server_sfgroup = array_shift($user_groups);

        //if user has group then put one of them otherwise put DEFAULT GROUP ID
        if($server_sfgroup) $etva_server->setsfGuardGroup($server_sfgroup);
        else $etva_server->setsfGuardGroup(sfGuardGroupPeer::getDefaultGroup());
               

        foreach($collNetworks as $coll) $etva_server->addEtvaNetwork($coll);

        $i = 0;
        foreach($collLvs as $coll){
            $server_disk = new EtvaServerLogical();
            $server_disk->setEtvaLogicalvolume($coll);
            $server_disk->setBootDisk($i);            
            $etva_server->addEtvaServerLogical($server_disk);

            $i++;
        }                


        //update some data from agent response
        $vm = (array) $returned_object['VM'];
        $etva_server->initData($vm);

        //$etva_server->setEtvaNode($etva_node);
        $etva_server->setEtvaCluster($etva_node->getEtvaCluster());

        
        try
        {
            $etva_server->save();
        }
        catch(Exception $e){
            $msg = $e->getMessage();
            $result = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg,'info'=>$msg);
            $return = $this->setJsonError($result);
            return  $this->renderText($return);
        }

        // assign To etva_node
        $etva_server->assignTo($etva_node);

        $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_OK_CREATE_,array('%name%'=>$server['name']));
        $message = Etva::getLogMessage(array('name'=>$server['name']), EtvaServerPeer::_OK_CREATE_);
        $this->dispatcher->notify(new sfEvent($etva_node->getName(), 'event.log', array('message' => $message)));


        $result = array('success'=>true,
                        'agent'=>$response['agent'],
                        'insert_id'=>$etva_server->getId(),
                        'response'=>$msg_i18n);

        $return = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        // if is browser request return text renderer
        return  $this->renderText($return);                               

    }
    

    /**
     * sets response http header to json and status code and convert to json $info
     *
     *
     * @param string $info
     * @param int $statusCode
     * @return string json string representation
     *
     */
    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }

    
}
