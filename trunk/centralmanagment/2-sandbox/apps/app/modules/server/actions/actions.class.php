<?php
/**
 * server actions.
 *
 * @package    centralM
 * @subpackage server
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
class serverActions extends sfActions
{
    /**
     * shows server migrate/move Extjs template
     *
     */    
    public function executeServer_Migrate()
    {
    }
    

    /**
     * shows server edit Extjs template
     *
     */
    public function executeServer_Edit()
    {
    }

    /**
     * shows server remove Extjs template
     *
     */
    public function executeServer_Remove()
    {
    }

    /**
     * shows server stop Extjs template
     *
     */
    public function executeServer_Stop()
    {
    }

    /**
      * Extjs template for vm devices
      */
    public function executeServer_ManageDevices(sfWebRequest $request)
    {
    }


    /**
     * list server snapshots Extjs template
     *
     */    
    public function executeServer_Snapshots()
    {
    }

    /**
     * shows server create snapshot Extjs template
     *
     */
    public function executeCreatesnapshot()
    {
    }

    private function checkFtpDir($url){
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

    /**
      * Check the given url for avaiability
      */
    private function validateLocationUrl($url){
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

    /**
     *
     *
     * process server edit
     *
     * request object is like this;
     * <code>
     * $request['server'] = array('id'                  =>$id,
     *                            'name'                =>$name,
     *                            'mem'                 =>$mem,
     *                            'description'         =>$description,
     *                            'cpuset'              =>$cpuset,
     *                            'boot'                =>$boot,
     *                            'cdrom'                =>$cdrom,
     *                            'location'            =>$location,
     *                            'vnc_keymap'          =>$vnc_keymap,
     *                            'vnc_keymap_default'  =>$vnc_keymap_default,
     *                            'networks'            =>array(array('port'=>$port,'vlan_id'=>$vlan_id,'mac'=>$mac,'intf_model'=>$intf_model)),
     *                            'disks'               =>array(array('id'=>$id,'disk_type'=>$disk_type))
     *                      )
     * </code>
     * 
     * @param sfWebRequest $request A request object
     * @return string json string representation
     *
     */
    public function executeJsonEdit(sfWebRequest $request)
    {
        $server = json_decode($request->getParameter('server'),true);
        $sid = isset($server['id']) ? $server['id'] : '';
        


        if($server['boot'] == 'location'){
            $valid = $this->validateLocationUrl($server['location']);
            if($valid == false){
                $msg_i18n = $this->getContext()->getI18N()->__('Could not validate the location URL!');
                $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
                $error = $this->setJsonError($error);
                return $this->renderText($error);
            }
        }

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);            
            $message = Etva::getLogMessage(array('name'=>$server['name'],'info'=>$server_log), EtvaServerPeer::_ERR_EDIT_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $etva_node = $etva_server->getEtvaNode();

        $clone_server = $etva_server->copy();
        $server_va = new EtvaServer_VA($clone_server);
        $response = $server_va->send_edit($etva_node, $etva_server, $server);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);
        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            if($response['error'] == EtvaNodePeer::_ERR_DEVICE_ATTACHED_){
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_DEVICE_ATTACHED_,array('%dev%'=>$response['dev']));
                $response['error'] = $msg_i18n;
            }

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

    }


    /**
     *
     *
     * load server info for editing. Returns server info
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     * @return string json string representation of array('success'=>true,'total'=>$total, 'data'=>$server_data)
     *
     */
    public function executeJsonLoad(sfWebRequest $request)
    {        

        $count = 0;
        $id = $request->getParameter('id');
        if($etva_server = EtvaServerPeer::retrieveByPK($id)) $count = 1;
        else{

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $etva_node = $etva_server->getEtvaNode();        

        $server_array = $etva_server->toArray(BasePeer::TYPE_FIELDNAME);
        if( $etva_node ){
            $server_array['node_id'] = $etva_node->getId();
            $server_array['node_hypervisor'] = $etva_node->getHypervisor();
            $server_array['node_ncpus'] = $etva_node->getCputotal();
            $server_array['node_state'] = $etva_node->getState();
            $server_array['can_create_vms'] = $etva_node->canCreateVms();
            $server_array['node_freememory'] = $etva_node->getMemfree();
            $server_array['node_maxmemory'] = $etva_node->getMaxMem();
            $server_array['unassigned'] = false;
        } else {
            $server_array['unassigned'] = true;
        }

        $server_array['vm_is_running'] = ($etva_server->isRunning()) ? true : false;

        $all_shared = $etva_server->isAllSharedLogicalvolumes();
        $server_array['all_shared_disks'] = $all_shared;

        $has_snapshots = $etva_server->hasSnapshots();
        $server_array['has_snapshots_disks'] = $has_snapshots;

        $server_array['has_snapshots_support'] = $etva_server->hasSnapshotsSupport() ? true : false;

        $server_array['has_devices'] = $etva_server->hasDevices();


        /*
         * Disk stuff
         */
        $srv_disks = EtvaServerLogicalQuery::create()
            ->useEtvaServerQuery()
                ->filterById($id)
            ->endUse()
            ->find();

        $server_array['server_has_disks'] = ($srv_disks->isEmpty()) ? False: True;

        /*
         * Network interfaces stuff
         */
        $srv_ifs = EtvaNetworkQuery::create()
            ->filterByServerId($id)
            ->find();

        $server_array['server_has_netifs'] = ($srv_ifs->isEmpty()) ? False: True;

        if( $features = $etva_server->getFeatures() ){
            $features_arr = (array)json_decode($features);
            foreach($features_arr as $f=>$v){
                $fk = "feature_" . $f;
                $server_array[$fk] = $v;
            }
        }


        /*
         * keymap stuff
         */
        $vnc_keymap = EtvaSettingPeer::retrieveByPk('vnc_keymap');
        $keymap_val = $vnc_keymap->getValue();

        $keymap_data = array('vnc_keymap' => $etva_server->getVnckeymap(),
                            'vnc_keymap_default' => $etva_server->getVncKeymapDefault(),
                            'keymap_default'=>$keymap_val);


        $return = array(
                       'success' => true,
                       'total' => $count,
                       'data'  => array_merge($server_array,$keymap_data)

        );

        $result = json_encode($return);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);

    }

    public function executeJsonGAApps(sfWebRequest $request){
        $id = $request->getParameter('sid');
            
        if($etva_server = EtvaServerPeer::retrieveByPK($id)) 
            $count = 1;
        else{
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        $ga_info = $etva_server->getGaInfo();
        $ga_obj = json_decode($ga_info);

        $res_obj = array();
        $ga_arr = (array)$ga_obj;
        $i = (array)$ga_arr['applications'];
        $applications = $i['applications'];

        foreach($applications as &$app){
            $app_obj = array();
            $app_obj['name'] = $app;
            $res_obj[] = $app_obj;
        }
        
        $return = array(
                       'success' => true,
                       'total' => sizeof($applications), 
                       'data'  => $res_obj
        );

        $result = json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeJsonGAInterfaces(sfWebRequest $request){
        $id = $request->getParameter('sid');
            
        if($etva_server = EtvaServerPeer::retrieveByPK($id)) 
            $count = 1;
        else{
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        $ga_info = $etva_server->getGaInfo();
        $ga_obj = json_decode($ga_info);

        $res_obj = array();
        $ga_arr = (array)$ga_obj;
        $i = (array)$ga_arr['network-interfaces'];
        $interfaces = $i['interfaces'];

        foreach($interfaces as &$interface){
            $if_obj = array();
            $if_obj['inet'] = $interface->inet;
            $if_obj['inet6'] = $interface->inet6;
            $if_obj['hw'] = $interface->hw;
            $if_obj['name'] = $interface->name;
            $res_obj[] = $if_obj;
        }
        
        $return = array(
                       'success' => true,
                       'total' => sizeof($interfaces), 
                       'data'  => $res_obj
        );

        $result = json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeJsonGADisks(sfWebRequest $request){
        $id = $request->getParameter('sid');
            
        if($etva_server = EtvaServerPeer::retrieveByPK($id)) 
            $count = 1;
        else{
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        $ga_info = $etva_server->getGaInfo();
        $ga_obj = json_decode($ga_info);

        $res_obj = array();
        $ga_arr = (array)$ga_obj;
        $d = (array)$ga_arr['disks-usage'];
        $disks = $d['disks'];

        foreach($disks as &$disk){
            $d_obj = array();
            $d_obj['fs'] = $disk->fs;
            $d_obj['path'] = $disk->path;
            $d_obj['used'] = $disk->used;
            $d_obj['total'] = $disk->total;
            $res_obj[] = $d_obj;
        }
        
        $return = array(
                       'success' => true,
                       'total' => sizeof($disks), 
                       'data'  => $res_obj
        );

        $result = json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeJsonPlonePack(sfWebRequest $request){
        $id = $request->getParameter('id');
        
        if($etva_server = EtvaServerPeer::retrieveByPK($id)) 
            $count = 1;
        else{
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        // Try to refresh data
        $etva_node = $etva_server->getEtvaNode();
        $server_va = new EtvaServer_VA($etva_server);

        error_log("before sending pack cmd");

        $response = $server_va->plonePack($etva_node);
        error_log("after sending pack cmd");


        if($response['success']){
            $return = array(
                'success'   => true,
                'status'    => $msg->etasp->pack->status
            );
   


            $msg = $response['response'];
            
            $return = array(
                           'success' => true,
                           'data'  => $msg,
            );
        
            $result = json_encode($return);
    
            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $result;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($result);
        }else{
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }
    }

    /**
     *
     *
     * returns plone info.  
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     * @return string json string representation of array('success'=>true,'total'=>$total, 'data'=>$server_data)
     *
     */
    public function executeJsonLoadPlone(sfWebRequest $request){
                
        $id = $request->getParameter('id');
            
        if($etva_server = EtvaServerPeer::retrieveByPK($id)) 
            $count = 1;
        else{
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        $ga_info = $etva_server->getGaInfo();
        $ga_obj = json_decode($ga_info);

        $etasp = $ga_obj->etasp;
        $plone_obj = $etasp->getInstanceMetadata;
        $plone_obj1 = $etasp->getResourceUsage;
        $plone_obj2 = $etasp->getDatabaseInfo;
        
        $res_obj = (object) array_merge((array)$plone_obj, (array)$plone_obj1, (array)$plone_obj2);
        $return = array(
                       'success' => true,
                       'total' => $count, 
                       'data'  => $res_obj,
        );

        $result = json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }


    /**
     *
     *
     * Calls the guest agent to get his info.  
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     * @return string json string representation of array('success'=>true,'total'=>$total, 'data'=>$server_data)
     *
     */
    public function executeJsonLoadGA(sfWebRequest $request)
    {        
        $count = 0;
        $id = $request->getParameter('id');

        if($etva_server = EtvaServerPeer::retrieveByPK($id)) 
            $count = 1;
        else{
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        // Try to refresh data
        $etva_node = $etva_server->getEtvaNode();
        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->getGAInfo($etva_node);

        $ga_info = $etva_server->getGaInfo();
        $ga_obj = json_decode($ga_info);

        $res_obj = array();
        $ga_arr = (array)$ga_obj;
        $heartbeat = (array)$ga_arr['heartbeat'];
        $res_obj['hostname'] = $ga_arr['host-name']->name;
        $res_obj['os-version'] = $ga_arr['os-version']->version;
        $res_obj['active-user'] = $ga_arr['active-user']->name;
        $res_obj['heartbeat'] = date(DATE_RFC822, $ga_arr['heartbeat']->timestamp);
        $res_obj['free-ram'] = $heartbeat['free-ram'];
        $res_obj['state'] = $etva_server->getGaState(); 
        
        $return = array(
                       'success' => true,
                       'total' => $count,
                       'data'  => $res_obj
        );

        $result = json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

        $return = array(
                       'success' => true,
                       'total' => $count,
                       'data'  => array_merge($server_array,$keymap_data)

        );

        $result = json_encode($return);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);

    }
    
    /**
     *
     *
     * process server migrate
     * live server migration between nodes
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['nid'] = $id; //node destination ID
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     * @return string json string representation of array('success'=>true,'agent'=>$agent, 'response'=>$response)
     *
     */
    public function executeJsonMigrate(sfWebRequest $request)
    {
        $sid = $request->getParameter('id');
        $nid = $request->getParameter('nid');

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$server_log), EtvaServerPeer::_ERR_MIGRATE_UNKNOWN_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$to_etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$node_log), EtvaServerPeer::_ERR_MIGRATE_ );
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $from_etva_node = $etva_server->getEtvaNode();        

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_migrate($from_etva_node, $to_etva_node);        

        if($response['success']){
            $response_ga = $server_va->getGAInfo($to_etva_node);   // update GA Info

            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }        

    }

    /**
     *
     *
     * move server to other node.
     * Removes server from current node and add to other
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['nid'] = $id; //node destination ID
     * </code>
     *
     * @param sfWebRequest $request A request object
     *     
     * @return string json string representation of array('success'=>true,'agent'=>$agent, 'response'=>$response)
     *
     */
    public function executeJsonMove(sfWebRequest $request)
    {
        $sid = $request->getParameter('id');
        $nid = $request->getParameter('nid');

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$server_log), EtvaServerPeer::_ERR_MOVE_UNKNOWN_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$to_etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$node_log), EtvaServerPeer::_ERR_MOVE_ );
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $from_etva_node = $etva_server->getEtvaNode();

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_move($from_etva_node, $to_etva_node);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

    }

    public function executeJsonUnassign(sfWebRequest $request)
    {
        $sid = $request->getParameter('id');

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$server_log), EtvaServerPeer::_ERR_MOVE_UNKNOWN_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $from_etva_node = $etva_server->getEtvaNode();

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_unassign($from_etva_node);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

    }
    public function executeJsonAssign(sfWebRequest $request)
    {
        $sid = $request->getParameter('id');
        $nid = $request->getParameter('nid');

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$server_log), EtvaServerPeer::_ERR_MOVE_UNKNOWN_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$to_etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$node_log), EtvaServerPeer::_ERR_MOVE_ );
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_assign($to_etva_node);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

    }
    
    /**
     *
     * display networks graph images template for the server
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeGraph_networkImage(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->networks = $etva_server->getEtvaNetworks();
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle(sprintf("%s :: %s",$etva_server->getName(),'Network interfaces'));

        $this->setLayout('graph_image');


    }

    /**
     *
     * display cpu load graph images template for the server
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeGraph_nodeLoadImage(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->node = $etva_server->getEtvaNode();
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle(sprintf("%s :: %s",$this->node->getName(),$this->getContext()->getI18N()->__(NodeLoadRRA::getName())));

        $this->setLayout('graph_image');
    }

    /**
     *
     * display disks graph images template for the server
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */    
    public function executeGraph_diskImage(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->disks = $etva_server->getEtvaLogicalvolumes();

        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle(sprintf("%s :: %s",$etva_server->getName(),$this->getContext()->getI18N()->__('Disks')));

        $this->setLayout('graph_image');
    }



    /**
     *
     * display cpu percentage image template for the server
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeGraph_cpu_perImage(sfWebRequest $request)
    {

        $this->server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle(sprintf("%s :: %s",$this->server->getName(),'CPU %'));

        $this->setLayout('graph_image');

    }

    /**
     *
     * display mem percentage image template for the server
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeGraph_memImage(sfWebRequest $request)
    {

        $this->server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle(sprintf("%s :: %s",$this->server->getName(),$this->getContext()->getI18N()->__('Memory')));

        $this->setLayout('graph_image');

    }



    /**
     *
     * returns cpu utilization png image from rrd data file
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */    
    public function executeGraph_cpu_perPNG(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');


        $cpu_per_rra = new ServerCpuUsageRRA($etva_node->getUuid(),$etva_server->getUuid());

        $title_i18n = sprintf("%s :: %s",$etva_server->getName(),$this->getContext()->getI18N()->__(ServerCpuUsageRRA::getName()));
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $cpu_per_rra->getGraphImg($title_i18n,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;


    }

    /**
     *
     * returns mem percentage png image from rrd data file
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */  
    public function executeGraph_mem_perPNG(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');


        $mem_rra = new ServerMemoryUsageRRA($etva_node->getUuid(),$etva_server->getUuid());

        $title_i18n = sprintf("%s :: %s",$etva_server->getName(),$this->getContext()->getI18N()->__(ServerMemoryUsageRRA::getName()));

        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $mem_rra->getGraphImg($title_i18n,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;


    }

    /**
     *
     * returns mem utilization png image from rrd data file
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeGraph_mem_usagePNG(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');


        $mem_rra = new ServerMemoryRRA($etva_node->getUuid(),$etva_server->getUuid());

        $title_i18n = sprintf("%s :: %s",$etva_server->getName(),$this->getContext()->getI18N()->__(ServerMemoryRRA::getName()));
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $mem_rra->getGraphImg($title_i18n,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;


    }




    /*
     * RRA data xports (XML)
     */

    /**
     *
     * export xml cpu percentage from server rrd data file
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * $request['step'] = $step;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeXportCpu_perRRA(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $cpu_per_rra = new ServerCpuUsageRRA($etva_node->getUuid(),$etva_server->getUuid());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($cpu_per_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;


    }

    /**
     *
     * export xml mem percentage from server rrd data file
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * $request['step'] = $step;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeXportMem_perRRA(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $mem_per_rra = new ServerMemoryUsageRRA($etva_node->getUuid(),$etva_server->getUuid());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($mem_per_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;


    }

    /**
     *
     * export xml mem utilization from server rrd data file
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * $request['step'] = $step;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeXportMem_usageRRA(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $mem_rra = new ServerMemoryRRA($etva_node->getUuid(),$etva_server->getUuid());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($mem_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;


    }

    /**
     *
     * shows server wizard Extjs template
     *
     * request object is like this;
     * <code>
     * $request['nid'] = $nid; //node ID          
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    // called by 'Add server wizard' button
    public function executeWizard(sfWebRequest $request)
    {
        $disk_vg = '';
        $this->etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('nid'));
        $etva_cluster = $this->etva_node->getEtvaCluster();
        $this->diskfile = $etva_cluster->countEtvaPhysicalvolumes() > 0 ? false : true;

        $c = new Criteria();
        $c->add(EtvaVolumegroupPeer::VG,sfConfig::get('app_volgroup_disk_flag'));
        $disk_vgs = $this->etva_node->getEtvaNodeVolumegroupsJoinEtvaVolumegroup($c);

        if($disk_vgs && (!$disk_vgs->isEmpty())) $disk_vg = $disk_vgs[0]->getEtvaVolumegroup();

        //if(!$disk_vg) return sfView::NONE;
        
        $this->max_size_diskfile = ($disk_vg) ? $disk_vg->getFreesize() : 0;

        // remove session macs for cleanup the wizard
        $this->getUser()->getAttributeHolder()->remove('macs_in_wizard');
    }

    /**
     *
     * shows server main panel view Extjs template
     *
     */
    public function executeView(sfWebRequest $request)
    {
        $this->server_tableMap = EtvaServerPeer::getTableMap();
        $this->sfGuardGroup_tableMap = sfGuardGroupPeer::getTableMap();
        //$this->network_tableMap = EtvaNetworkPeer::getTableMap();

    }


    /**
     *
     *
     * creates virtual machine
     *
     * request object is like this;
     * <code>
     * $request['nid'] = $nid; //node ID
     * $request['server'] = array('name'                  =>$name,
     *                            'description'           =>$description,
     *                            'ip'                    =>$ip,
     *                            'mem'         =>$mem,
     *                            'cpuset'              =>$cpuset,
     *                            'boot'                =>$boot,                            
     *                            'location'            =>$location,
     *                            'vm_type'          =>$vm_type,
     *                            'vnc_keymap_default'  =>$vnc_keymap_default,
     *                            'networks'            =>array(array('port'=>$port,'vlan_id'=>$vlan_id,'mac'=>$mac)),
     *                            'disks'               =>array(array('id'=>$id))
     *                      )
     * </code>
     *
     * @param sfWebRequest $request A request object
     * @return string json string representation of array('success'=>true,'agent'=>$agent, 'response'=>$response)
     *
     */

    private function errorUrl(){
        $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>'URL not found. Insert a valid URL', 'info'=>'URL not found!');
        return $this->setJsonError($error);
//        return $this->renderText($error);
    }
   /*
    * create virtual machine
    * sends soap request and stores info
    */
    public function executeJsonCreate(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');
        $server = json_decode($request->getParameter('server'),true);
    
        if($server['boot'] == 'location'){

            $valid = $this->validateLocationUrl($server['location']);
            if($valid == false){
                $msg_i18n = $this->getContext()->getI18N()->__('Could not validate the location URL!');
                $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
                $error = $this->setJsonError($error);
                return $this->renderText($error);
            }


//            error_log('[INFO] '.$server['location']);
//            $url_obj = parse_url($server['location']);
//            error_log($url_obj['scheme']);
//            $valid = false;
//
//            if($url_obj['scheme'] == 'ftp'){
//                $valid = $this->checkFtpDir($server['location']);                
//            }else if(preg_match('/^(http|https)$/', $url_obj['scheme']) && get_headers($server['location'])){
//                $valid = true;
//            }else if($url_obj['scheme'] == 'nfs'){
//                $valid = true;
//            }
//
//            if($valid == false){
//                $msg_i18n = $this->getContext()->getI18N()->__('Could not validate the location URL!');
//                $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
//                $error = $this->setJsonError($error);
//                return $this->renderText($error);
//            }
        }
//        if($server['boot'] == 'location'){
//            error_log('[INFO] '.$server['location']);
//            if(preg_match('/^ftp/', $server['location'])){
//                error_log("[INFO] FTP URL detected");
//                $handle = fopen($server['location'], 'r'); // or return $this->renderText($this->errorUrl());
//                fclose($handle);
//            }else if(preg_match('/^http/', $server['location']) && !get_headers($server['location'])){
//                //$msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
//                //$error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);
//                $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>'URL not found. Insert a valid URL', 'info'=>'URL not found!');
//                $error = $this->setJsonError($error);
//                return $this->renderText($error);
//            }
//        }

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$server['name'],'info'=>$node_log), EtvaServerPeer::_ERR_CREATE_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }
        

        $etva_server = new EtvaServer();        
        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_create($etva_node,$server);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }        

    }
  
    public function executeJsonCheckUrl(sfWebRequest $request){
        $url = $request->getParameter('url');
        $res = $this->validateLocationUrl($url);
        
        if($res == false){
            $msg_i18n = $this->getContext()->getI18N()->__('Could not validate the url.');
            $result = array('success'=>false,'error'=>$msg_i18n);
        }else{
            $result = array('success'=>true); 
        }

        $jsonObj = json_encode($result);
        if(sfConfig::get('sf_environment') == 'soap') return $result;
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($jsonObj);
    }

    /**
     *
     *
     * removes virtual machine
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['keep_fs'] = 0 | 1
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     * @return string json string representation of array('success'=>true,'agent'=>$agent, 'response'=>$response)
     *
     */
    // removes server
    // args: server ID
    //       keep_fs (keep file system??)
    // returns json message
    public function executeJsonRemove(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        $kfs = (boolean) $request->getParameter('keep_fs');        

        $keep_fs = false;
        if(is_bool($kfs)) $keep_fs = $kfs;

        if(!$etva_server = EtvaServerPeer::retrieveByPK($id)){

            $msg = Etva::getLogMessage(array('id'=>$id), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log

            $message = Etva::getLogMessage(array('info'=>$msg), EtvaServerPeer::_ERR_REMOVE_ID_);
            $this->dispatcher->notify(new sfEvent($error['agent'], 'event.log', array('message' => $message,'priority'=>EtvaEventLogger::ERR)));


            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        $etva_node = $etva_server->getEtvaNode();
                
        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_remove($etva_node,$keep_fs);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }      

    }    

    /**
     *
     *
     * starts virtual machine
     *
     * request object is like this;
     * <code>
     * $request['nid'] = $nid; //node ID
     * $request['server'] = $name // server name
     * </code>
     *
     * @param sfWebRequest $request A request object
     * @return string json string representation of array('success'=>true,'agent'=>$agent, 'response'=>$response)
     *
     */
    /*
     * starts virtual machine
     */
    public function executeJsonStart(sfWebRequest $request)
    {
        $to_assign = false;

        if( $nid = $request->getParameter('nid') ){

            if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                //notify event log
                $msg_i18n = Etva::makeNotifyLogMessage(sfConfig::get('config_acronym'),
                                                                        EtvaNodePeer::_ERR_NOTFOUND_ID_, array('id'=>$nid),
                                                                        EtvaServerPeer::_ERR_START_, array('name'=>$server));
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }

            $server = $request->getParameter('server');
            if(!$etva_server = $etva_node->retrieveServerByName($server)){

                //notify event log
                $msg_i18n = Etva::makeNotifyLogMessage($etva_node->getName(),
                                                                        EtvaServerPeer::_ERR_NOTFOUND_,array('name'=>$server),
                                                                        EtvaServerPeer::_ERR_START_,array('name'=>$server));

                $error = array('agent'=>$etva_node->getName(),'success'=>false,'error'=>$msg_i18n);

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }
        } else {

            if( $sid = $request->getParameter('sid') ){
                $etva_server = EtvaServerPeer::retrieveByPK($sid);
            } else if( $server = $request->getParameter('server') ){
                $etva_server = EtvaServerPeer::retrieveByName($server);
            }

            if(!$etva_server){

                //notify event log
                $msg_i18n = Etva::makeNotifyLogMessage($etva_node->getName(),
                                                                        EtvaServerPeer::_ERR_NOTFOUND_,array('name'=>$server),
                                                                        EtvaServerPeer::_ERR_START_,array('name'=>$server));

                $error = array('agent'=>$etva_node->getName(),'success'=>false,'error'=>$msg_i18n);

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }

            $nodes_toassign = $etva_server->listNodesAssignTo();
            if( count($nodes_toassign) ){
                $etva_node = $nodes_toassign[0];    // get first
                $to_assign = true;
            } else {

                //notify event log
                $msg_i18n = Etva::makeNotifyLogMessage(sfConfig::get('config_acronym'),
                                                                        EtvaServerPeer::_ERR_NO_NODE_TO_ASSIGN_, array(),
                                                                        EtvaServerPeer::_ERR_START_, array('name'=>$server));
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }
        }

        $server_va = new EtvaServer_VA($etva_server);

        if( $to_assign ){
            $response = $server_va->send_assign($etva_node);

            if(!$response['success']){

                if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

                $return = $this->setJsonError($response);
                return  $this->renderText($return);
            }
        }

        $response = $server_va->send_start($etva_node);

        if($response['success']){

            $response_ga = $server_va->getGAInfo($etva_node);   // update GA Info

            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);
        } else {

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }      
    }


    /**
     *
     *
     * stops virtual machine
     *
     * request object is like this;
     * <code>
     * $request['nid'] = $nid; //node ID
     * $request['server'] = $name // server name
     * </code>
     *
     * @param sfWebRequest $request A request object
     * @return string json string representation of array('success'=>true,'agent'=>$agent, 'response'=>$response)
     *
     */
    public function executeJsonStop(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $server = $request->getParameter('server');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            //notify event log
            $msg_i18n = Etva::makeNotifyLogMessage(sfConfig::get('config_acronym'),
                                                                    EtvaNodePeer::_ERR_NOTFOUND_ID_, array('id'=>$nid),
                                                                    EtvaServerPeer::_ERR_STOP_, array('name'=>$server));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$etva_server = $etva_node->retrieveServerByName($server)){

            //notify event log
            $msg_i18n = Etva::makeNotifyLogMessage($etva_node->getName(),
                                                                    EtvaServerPeer::_ERR_NOTFOUND_,array('name'=>$server),
                                                                    EtvaServerPeer::_ERR_STOP_,array('name'=>$server));

            $error = array('agent'=>$etva_node->getName(),'success'=>false,'error'=>$msg_i18n);

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $destroy = $request->getParameter('destroy') ? 1 : 0;
        $force = $request->getParameter('force') ? 1 : 0;

        $extra = array('destroy'=>$destroy, 'force'=>$force);

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_stop($etva_node,$extra);

        if($response['success']){
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);
        } else {

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }      
    }

    /**
     *
     *
     * set boot virtual machine
     *
     * request object is like this;
     * <code>
     * $request['boot'] = $boot; //location, cdrom,filesystem,pxe
     * $request['id'] = $id // server id
     * $request['data'] = Array containing pairs 'field'=>$field, 'value'=>$value for extra boot info like cdrom ISO
     * </code>
     *
     * @param sfWebRequest $request A request object
     * @return string json string representation of array('success'=>true)
     *
     */
    public function executeJsonSetBoot(sfWebRequest $request)
    {
        if(!$request->isMethod('post') && !$request->isMethod('put')){
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        if(!$etva_server = EtvaServerPeer::retrieveByPk($request->getParameter('id'))){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$request->getParameter('id')));
            $info = array('success'=>false,'error'=>$msg_i18n);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $data = json_decode($request->getParameter('data'),true);
        $to_insert = $data;

        $boot_field = $request->getParameter('boot');

        switch($boot_field){
            case 'filesystem' :
            case 'pxe'        :
                                if(!$etva_server->getCdrom()) $to_insert[] = array('field'=>'location','value'=>'');
                                break;

        }                      
        
        $to_insert[] = array('field'=>'boot','value'=>$boot_field);

        foreach($to_insert as $insert_data)
        {
            if($insert_data['field'] && isset($insert_data['value']))
                $etva_server->setByName($insert_data['field'], $insert_data['value'], BasePeer::TYPE_FIELDNAME);
        }

        $etva_server->save();

        $result = array('success'=>true);
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
        
    }    

    /**
     * perform operations on server vnc_keymap
     *
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['method'] = update | null //operation to perform (update). Default will list value
     * $request['vnc_keymap'] = $vnc_keymap
     * $request['vnc_keymap_default'] = 0 | 1
     * </code>
     *
     * @param sfWebRequest $request A request object
     * @return string json string representation of array('success'=>true,'data'=>$data)
     *
     */
    public function executeJsonKeymap(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        $method = $request->getParameter('method');
        $keymap = $request->getParameter('vnc_keymap');
        $default_keymap = $request->getParameter('vnc_keymap_default');

        if(!$etva_server = EtvaServerPeer::retrieveByPK($id)){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$id), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$keymap,'server'=>'','info'=>$server_log), EtvaServerPeer::_ERR_VNCKEYMAP_CHANGE_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        $server = $etva_server->getName();
        $server_uuid = $etva_server->getUuid();

        $etva_node = $etva_server->getEtvaNode();

        switch($method){
            case 'update':

                        $params = array('uuid'       => $server_uuid,
                                        'vnc_keymap' => $keymap);

                        $response = $etva_node->soapSend('set_vnc_options',$params);

                        if(!$response['success']){

                            $result = $response;

                            //notify system log
                            $message = Etva::getLogMessage(array('name'=>$keymap,'info'=>$response['info'],'server'=>$etva_server->getName()), EtvaServerPeer::_ERR_VNCKEYMAP_CHANGE_);
                            $this->dispatcher->notify(
                                new sfEvent($etva_node->getName(),
                                        'event.log',
                                        array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
                            ));

                            $return = $this->setJsonError($result);
                            return  $this->renderText($return);
                        }

                        $etva_server->setVncKeymapDefault($default_keymap);
                        $etva_server->setVnckeymap($keymap);
                        $etva_server->save();

                        //notify system log
                        $message = Etva::getLogMessage(array('name'=>$keymap,'server'=>$etva_server->getName()), EtvaServerPeer::_OK_VNCKEYMAP_CHANGE_);
                        $this->dispatcher->notify(
                            new sfEvent($etva_node->getName(),
                                        'event.log',
                                        array('message' =>$message)
                        ));

                        $response_decoded = (array) $response['response'];
                        $returned_status = $response_decoded['_okmsg_'];

                        $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_OK_VNCKEYMAP_CHANGE_,array('%name%'=>$keymap,'%server%'=>$etva_server->getName()));

                        $msg = $response;
                        $msg['response'] = $msg_i18n;

                        break;
                default:
                        $vnc_keymap = EtvaSettingPeer::retrieveByPk('vnc_keymap');
                        $keymap_val = $vnc_keymap->getValue();

                        $data = array('vnc_keymap' => $etva_server->getVnckeymap(),
                                      'vnc_keymap_default' => $etva_server->getVncKeymapDefault(),
                                      'keymap_default'=>$keymap_val);

                        $msg =  array('success'=>true,'data'=>$data);

                        break;

        }


        $result = json_encode($msg);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);


    }

    /**
     * returns server info in json for Extjs grid
     *
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID     
     * </code>
     *
     * @param sfWebRequest $request A request object
     * @return string json string representation of server info
     *
     */
    public function executeJsonGridInfo(sfWebRequest $request)
    {        

        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');
        $elements = array();

        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::ID, $request->getParameter('id'));

        // $this->etva_server = EtvaServerPeer::doSelectJoinsfGuardGroup($criteria);
        $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));

        $serverGroup = $this->etva_server->getsfGuardGroup();
        $serverGroupName = $serverGroup->getName();

        $lv = $this->etva_server->getEtvaLogicalvolume();
        $lvName = $lv->getLv();

        $etva_node = $this->etva_server->getEtvaNode();

        $data = $this->etva_server->toArray();
        $data['SfGuardGroupName'] = $serverGroupName;
        $data['LogicalvolumeId'] = $lvName;
        $elements[] = $data;
        $final = array('total' => count($elements),
                       'data'  => $elements,
                       'node_state'=>$etva_node->getState());
        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);

    }


    /*
     * list all servers
     * list 10 results
     */
    public function executeJsonListAll(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $query = EtvaServerQuery::create();

        $query = $query->useEtvaServerAssignQuery('ServerAssign','RIGHT JOIN')
                            ->useEtvaNodeQuery()
                            ->endUse()
                       ->endUse();

        $this->addSortQuery($query);

        $this->pager = $query->paginate( $page, $limit );

        $elements = array();
        $i = 0;

        # Get data from Pager
        foreach($this->pager as $item){            
            $elements[$i] = $item->toArray();
            $etva_node = $item->getEtvaNode();
            $elements[$i]['NodeName'] = $etva_node->getName();
            $i++;
        }

        $final = array(
            'total' => $this->pager->getNbResults(),            
            'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }


    /**
     * returns all servers of an node in json for Extjs grid with pager
     *
     *
     * request object is like this;
     * <code>
     * $request['nid'] = $nid; //node ID
     * $request['start'] = $startt; // start at record
     * $request['limit'] = $limit; // number of records
     * </code>
     *
     * @param sfWebRequest $request A request object
     * @return string json string representation of servers info
     *
     */   
    public function executeJsonGrid(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $query = EtvaServerQuery::create();

        if( $this->getRequestParameter("assigned") ){

            $nodeID = $this->getRequestParameter("nid");
            $query = $query->useEtvaServerAssignQuery('ServerAssign','LEFT JOIN')
                                ->filterByNodeId($nodeID)
                           ->endUse();
        }

        $this->addSortQuery($query);

        $etva_node = EtvaNodePeer::retrieveByPK($this->getRequestParameter("nid"));
        if(!$etva_node) return array('success'=>false);        

        $this->pager = $query->paginate( $page, $limit );


        $elements = array();
        $i = 0;

        # Get data from Pager
        foreach($this->pager as $item){            
            $elements[$i] = $item->toDisplay();
            $etva_vnc_ports = $item->getEtvaVncPorts();
            if(count($etva_vnc_ports) > 0){
                $etva_vnc_port = $etva_vnc_ports[0];
                $elements[$i]['vnc_port'] = $etva_vnc_port->getId();                
            }
            $all_shared = $item->isAllSharedLogicalvolumes();
            $elements[$i]['all_shared_disks'] = $all_shared;

            $has_snapshots = $item->hasSnapshots();
            $elements[$i]['has_snapshots_disks'] = $has_snapshots;

            $elements[$i]['has_snapshots_support'] = $item->hasSnapshotsSupport() ? true : false;

            $elements[$i]['has_devices'] = $item->hasDevices();

            if( $nodeID ) $elements[$i]['node_id'] = $nodeID;

            $i++;
        }


        $final = array(
            'total' => $this->pager->getNbResults(),
            'node_state'=>$etva_node->getState(),
            'node_initialize'=>$etva_node->getInitialize(),            
            'can_create_vms'=>$etva_node->canCreateVms(),
            'node_has_vgs'=>$etva_node->hasVgs(),
            'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }

    /**
     * Adds sort criteria
     * request object is like this;
     *
     * <code>
     * $request['sort'] = json encoded([{field: 'node_name',direction: 'ASC'},{field: 'name',direction: 'ASC'}])
     * or
     * $request['sort'] = $field_name; // file name to sort
     * $request['dir'] = $dir; // direction ASC or DESC
     * </code>
     *
     * @param Criteria $criteria
     *     
     *
     */
    protected function addSortCriteria(Criteria $criteria)
    {
        
        $sort = $this->getRequestParameter('sort');
        $sort_array = json_decode($sort,true);
        if(gettype($sort_array)=='array') $sort = $sort_array;
        
        if ($sort=='') return;

        $sort_els = $sort;
        if(!is_array($sort))
        {
        
            $sort_els = array(array('field'=>$sort,'direction'=>$this->getRequestParameter('dir')));

        }

        foreach($sort_els as $sort_info)
        {
            $field = $sort_info['field'];
            if(preg_match('/^node_(.*)/',$field,$matches)){
                $field = $matches[1];
                $column = EtvaNodePeer::translateFieldName(sfInflector::camelize($field), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

            }
            else $column = EtvaServerPeer::translateFieldName(sfInflector::camelize($field), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

            if ('asc' == strtolower($sort_info['direction']))
                $criteria->addAscendingOrderByColumn($column);
            else
                $criteria->addDescendingOrderByColumn($column);
                $criteria->setIgnoreCase(true);

        }
        
    }    
    /**
     * Adds sort query
     * request object is like this;
     *
     * <code>
     * $request['sort'] = json encoded([{field: 'node_name',direction: 'ASC'},{field: 'name',direction: 'ASC'}])
     * or
     * $request['sort'] = $field_name; // file name to sort
     * $request['dir'] = $dir; // direction ASC or DESC
     * </code>
     *
     * @param Criteria $criteria
     *     
     *
     */
    protected function addSortQuery($query)
    {
        
        $sort = $this->getRequestParameter('sort');
        $sort_array = json_decode($sort,true);
        if(gettype($sort_array)=='array') $sort = $sort_array;
        
        if ($sort=='') return;

        $sort_els = $sort;
        if(!is_array($sort))
        {
        
            $sort_els = array(array('field'=>$sort,'direction'=>$this->getRequestParameter('dir')));

        }

        foreach($sort_els as $sort_info)
        {
            $field = $sort_info['field'];
            if(preg_match('/^node_(.*)/',$field,$matches)){
                $field = $matches[1];
                //$column = EtvaNodePeer::translateFieldName(sfInflector::camelize($field), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
                $column = EtvaNodePeer::OM_CLASS.".".sfInflector::camelize($field);

            }
            else $column = EtvaServerPeer::OM_CLASS.".".sfInflector::camelize($field);
            //else $column = EtvaServerPeer::translateFieldName(sfInflector::camelize($field), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

            if ('asc' == strtolower($sort_info['direction'])){
                $query = $query->orderBy("$column",'asc');
            } else {
                $query = $query->orderBy("$column",'desc')
                                ->setIgnoreCase(true);
            }

        }
        
    }    


    /**     
     *
     * process server form data. binds request data with form and save
     *
     * @param sfWebRequest $request A request object
     * @param sfForm $form A form object
     *
     */
    protected function processJsonForm(sfWebRequest $request, sfForm $form)
    {
        // get submitted form elements
        $form_elems = $request->getParameter($form->getName());
        // retrieve mac pool elements number
        // $mac_pool = $request->getParameter('mac_pool');

        //generate random macs and add to form elements
        //    if(isset($mac_pool)){
        //        $macs = $this->generateMacPool($mac_pool);
        //        $macs = implode(',',$macs);
        //
        //        $form_elems['mac_addresses'] = $macs;
        //    }

        $form->bind($form_elems, $request->getFiles($form->getName()));

        if ($form->isValid())
        {
            $etva_server = $form->save();

            //$result = array('success'=>true,'insert_id'=>$etva_server->getId());
            $result = array('success'=>true, 'object'=>$etva_server->toArray());
            return $result;

        }
        else{
            $errors = array();
            foreach ($form->getErrorSchema() as $field => $error)
            $errors[$field] = $error->getMessage();
            $result = array('success'=>false,'error'=>$errors);
            return $result;
        }


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

    protected function returnJsonError($response){
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

        $return = $this->setJsonError($response);
        return  $this->renderText($return);
    }
    protected function returnJsonOK($response){

        $return = json_encode($response);

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;
        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return  $this->renderText($return);
    }

    /**
     * returns servers/nodes for Extjs tree template
     *
     * @return string json string representation
     *
     */
    public function executeJsonTree(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');       

        $tree = $this->generateTree();
        $json = json_encode($tree);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText ($json);
    }

     protected function generateTree()
    {

        $dc_criteria = new Criteria();
        $dc_criteria->addJoin(EtvaClusterPeer::ID, EtvaNodePeer::CLUSTER_ID, Criteria::LEFT_JOIN);
        $dc_criteria->setDistinct();

        $datacenters = EtvaClusterPeer::doSelect($dc_criteria);

        $show_cluster_contextmenu = $this->getUser()->isSuperAdmin();

        $aux_datacenter = array();
        foreach ($datacenters as $datacenter){

            $dc_nodes = $datacenter->getEtvaNodes();

            $datacenter_name = $datacenter->getName();
            $datacenter_id = $datacenter->getId();

            if(count($dc_nodes) == 0){
                $aux_datacenter[] = array('text'=>$datacenter->getName(),'type'=>'cluster','id'=>"d".$datacenter->getId(),
                        'singleClickExpand'=>false,'children'=>array(),'contextmenu'=>$show_cluster_contextmenu,'expanded'=>true, 'cls'=> 'x-tree-node-collapsed ');
                continue;
            }

            // only nodes from the datacenter
            $criteria = new Criteria();

            $sparenode_isFree = false;
            $sparenode_id = null;
            $sparenode = $datacenter->getSpareNode();
            if( $sparenode ){
                $sparenode_id = $sparenode->getId();
                $sparenode_isFree = $sparenode->isNodeFree() ? true : false;
            }

            error_log("TREE[INFO] Numer of '".$datacenter_name."' nodes : "+count($dc_nodes));

            //********************** old code

            $user_id = $this->getUser()->getId();

            $perm_dc = $this->getUser()->hasDatacenterCredential(array('admin', $datacenter->getId()));

            $aux = array();

            // unassigned servers
            $unassigned_servers = array();

            //BaseEtvaNode
            foreach ($dc_nodes as $node){
                //
                $node_name = $node->getName();
                $state = $node->getState();
                $initialize = $node->getInitialize();

                $node_qtip = $this->getContext()->getI18N()->__(EtvaNodePeer::_STATE_UP_,array('%name%'=>$node_name));

                switch($initialize)
                {
                    case EtvaNode_VA::INITIALIZE_OK :
                                                            $cls_node = 'active';

                                                            break;
                    case EtvaNode_VA::INITIALIZE_PENDING:
                                                            $cls_node = 'pending';
                                                            $node_qtip = $this->getContext()->getI18N()->__(EtvaNodePeer::_INITIALIZE_PENDING_,array('%name%'=>$node_name));
                                                            break;
                }

                if($state == EtvaNode::NODE_MAINTENANCE_UP){
                    $cls_node = 'active';
                } elseif($state == EtvaNode::NODE_FAIL_UP){
                    $cls_node = 'active';
                } elseif($state != EtvaNode::NODE_ACTIVE){
                    $cls_node = 'no-active';
                    $node_qtip = $this->getContext()->getI18N()->__(EtvaNodePeer::_STATE_DOWN_,array('%name%'=>$node_name));
                }

                $last_message = $node->getLastMessage();
                $iconCls = '';

                if( $node->getIssparenode() ) $iconCls = 'icon-sparenode';

                switch($node->getState()){
                    case EtvaNode::NODE_FAIL_UP :
                    case EtvaNode::NODE_FAIL : $iconCls = 'icon-fail' ;
                                                break;
                    case EtvaNode::NODE_MAINTENANCE_UP :
                    case EtvaNode::NODE_MAINTENANCE : $iconCls = 'icon-maintenance' ;
                                                break;
                }

                $message_decoded = json_decode($last_message,true);

                switch($message_decoded['priority']){
                    case EtvaEventLogger::ERR : $iconCls = 'icon-error';
                                                break;
                }

                if($message_decoded['message']) $node_qtip = $this->getContext()->getI18N()->__($message_decoded['message']);

                $has_servers_running = false;
                $aux_servers = array();
                foreach ($node->getServers() as $i => $server){

                    if($perm_dc or $this->getUser()->hasServerCredential(array('op', $server->getId()))){

                        $state_server = $server->getState();

                        $agent_server_port = $server->getAgentPort();
                        $agent_tmpl = $server->getAgentTmpl();
                        $vm_state = $server->getVmState();
                        $vm_is_running = $server->isRunning() ? true : false;

                        $cls_server = 'no-active';

                        if($vm_is_running)
                        {
                            $has_servers_running = true;
                            if($aoncontextmenugent_server_port)
                            {
                                if(!$state_server) $cls_server = 'some-active';
                                else $cls_server = 'active';
                            }
                            else $cls_server = 'active';

                        }else
                        {
                            if($agent_server_port)
                                if($state_server) $cls_server = 'some-active';
                        }

                        $child_id = 's'.$server->getID();

                        $srv_qtip = '';
                        $draggable =  ( $perm_dc ) ? true : false;

                        $all_shared = $server->isAllSharedLogicalvolumes();
                        $has_snapshots = $server->hasSnapshots();
                        $has_disks = $server->hasEtvaServerLogicals();
                        $has_devs = $server->hasDevices();

                        $srv_iconCls = ( $vm_is_running ) ? 'icon-vm-stat-ok': 'icon-vm-stat-nok';
                        $join_cls_server = $cls_server." ".$srv_iconCls;

                        # check if vm has etasp info
                        $ga_info = $server->getGaInfo();
                        $ga_obj = json_decode($ga_info);
                        $plone = ($ga_obj->etasp) ? 1 : 0;

                        $aux_servers[] = array(
                            'text'=>$server->getName(),'type'=>'server','id'=>$child_id,'node_id'=>$node->getID(),
                            'state'=>$state_server,'agent_tmpl'=>$agent_tmpl,'cls'=>$join_cls_server,
                            'url'=> $this->getController()->genUrl('server/view?id='.$server->getID()),
                            'vm_state'=>$vm_state,'vm_is_running'=>$vm_is_running,'unassigned'=>false,'all_shared'=>$all_shared,
                            'has_snapshots'=>$has_snapshots,'has_disks'=>$has_disks, 'has_devices'=>$has_devs,
                            'leaf'=>true, 'draggable'=>($perm_dc && $server->canMove()), 'qtip'=>$srv_qtip,
                            'ga_state'=>$server->getGaState(), 'ga_info'=>$server->getGaInfo(), 
                            'plone' => $plone, 'contextmenu'=>$perm_dc
                        );
                    }
                    
                }

                //node array fulfilling
                if(empty($aux_servers)){
                    if( $perm_dc ){   // only show node with empty servers if have permission on that node
                        $aux[] = array('text'=>$node_name,'type'=>'node','iconCls'=>$iconCls,'state'=>$state,'id'=>$node->getID(),'initialize'=>$initialize,'url'=>$this->getController()->genUrl('node/view?id='.$node->getID()),
                                        'cluster_id'=>$node->getClusterId(),'can_create_vms'=>$node->canCreateVms(),
                                        'children'=>$aux_servers,'expanded'=>true,'qtip'=>$node_qtip,'cls'=> 'x-tree-node-collapsed '.$cls_node, 'contextmenu'=>$perm_dc, 'initialize'=>$node->getInitialize(),'sparenodeid'=>$sparenode_id, 'has_servers_running'=>$has_servers_running, 'sparenodeIsFree'=>$sparenode_isFree);
                    }
                }else{
                    $aux[] = array('text'=>$node_name,'type'=>'node','iconCls'=>$iconCls,'state'=>$state,'id'=>$node->getID(),'initialize'=>$initialize,'cls'=>$cls_node,'url'=>$this->getController()->genUrl('node/view?id='.$node->getID()),
                            'cluster_id'=>$node->getClusterId(),'can_create_vms'=>$node->canCreateVms(),
                            'singleClickExpand'=>true,'qtip'=>$node_qtip,'children'=>$aux_servers,'contextmenu'=>$perm_dc, 'initialize'=>$node->getInitialize(),'sparenodeid'=>$sparenode_id, 'has_servers_running'=>$has_servers_running, 'sparenodeIsFree'=>$sparenode_isFree);
                }

            }

            $etva_unassigned_servers = EtvaServerQuery::create()
                ->filterByClusterId($datacenter->getId())
                ->useEtvaServerAssignQuery('ServerAssign','LEFT JOIN')
                    ->filterByNodeId(null)
                ->endUse()
                ->find();
            foreach ($etva_unassigned_servers as $i => $server){
                if($perm_dc or $this->getUser()->hasServerCredential(array('op', $server->getId()))){

                    $state_server = $server->getState();

                    $agent_server_port = $server->getAgentPort();
                    $agent_tmpl = $server->getAgentTmpl();
                    $vm_state = $server->getVmState();
                    $vm_is_running = $server->isRunning() ? true : false;

                    $cls_server = 'no-active';

                    if($vm_is_running)
                    {
                        $has_servers_running = true;
                        if($aoncontextmenugent_server_port)
                        {
                            if(!$state_server) $cls_server = 'some-active';
                            else $cls_server = 'active';
                        }
                        else $cls_server = 'active';

                    }else
                    {
                        if($agent_server_port)
                            if($state_server) $cls_server = 'some-active';
                    }

                    $child_id = 's'.$server->getID();

                    $srv_qtip = '';
                    $draggable =  ( $perm_dc ) ? true : false;

                    $all_shared = false;
                    $has_snapshots = $server->hasSnapshots();
                    $has_disks = $server->hasEtvaServerLogicals();
                    $has_devs = $server->hasDevices();
                    $nodes_toassign_arr = array();
                    $nodes_toassign = $server->listNodesAssignTo();
                    foreach($nodes_toassign as $i=>$node){
                        error_log( "NodeAssignTo server id=" . $server->getId() . " name=" . $server->getName() . "id=" . $node->getId() . " name=" . $node->getName() . " per_res=" . $node->getper_res() . " per_mem=" . $node->getper_mem() . " per_cpu=" . $node->getper_cpu() );
                        array_push($nodes_toassign_arr,$node->getId());
                    }

                    $srv_iconCls = ( $vm_is_running ) ? 'icon-vm-stat-ok': 'icon-vm-stat-nok';
                    $join_cls_server = $cls_server." ".$srv_iconCls;

                    # check if vm has etasp info
                    $ga_info = $server->getGaInfo();
                    $ga_obj = json_decode($ga_info);
                    $plone = ($ga_obj->etasp) ? 1 : 0;

                    $unassigned_servers[] = array(
                        'text'=>$server->getName(),'type'=>'server','id'=>$child_id,'node_id'=>null,
                        'state'=>$state_server,'agent_tmpl'=>$agent_tmpl,'cls'=>$join_cls_server,
                        'url'=> $this->getController()->genUrl('server/view?id='.$server->getID()),
                        'vm_state'=>$vm_state,'vm_is_running'=>$vm_is_running,'unassigned'=>true,'all_shared'=>$all_shared,
                        'has_snapshots'=>$has_snapshots,'has_disks'=>$has_disks, 'has_devices'=>$has_devs,
                        'nodes_toassign'=>$nodes_toassign_arr, //json_encode($nodes_toassign_arr),
                        'leaf'=>true, 'draggable'=>$draggable, 'qtip'=>$srv_qtip,
                        'ga_state'=>$server->getGaState(), 'ga_info'=>$server->getGaInfo(), 
                        'plone' => $plone,'contextmenu'=>$perm_dc
                    );
                }
            }

            $unassigned_node_name = $this->getContext()->getI18N()->__('unassigned');
            if( empty($unassigned_servers) ){
                if( $perm_dc ){
                    $aux[] = array('text'=>$unassigned_node_name,'type'=>'unassignednode','iconCls'=>'','state'=>1,'id'=>0,'initialize'=>'ok','url'=>$this->getController()->genUrl('node/viewUnassigned?cluster_id='.$datacenter->getId()),
                                'cluster_id'=>$datacenter->getId(), 'draggable'=>false,
                                'children'=>$unassigned_servers,'expanded'=>true,'qtip'=>'','cls'=> 'x-tree-node-collapsed ', 'contextmenu'=>$perm_dc);
                }
            } else {
                $aux[] = array('text'=>$unassigned_node_name,'type'=>'unassignednode','iconCls'=>'','state'=>1,'id'=>0,'initialize'=>'ok','url'=>$this->getController()->genUrl('node/viewUnassigned?cluster_id='.$datacenter->getId()),
                            'cluster_id'=>$datacenter->getId(), 'draggable'=>false,
                            'children'=>$unassigned_servers,'qtip'=>'','cls'=> '', 'contextmenu'=>$perm_dc);
            }

            //datacenter node
            if( $perm_dc || !empty($aux) ){
                $aux_datacenter[] = array('text'=>$datacenter->getName(),'type'=>'cluster','id'=>"d".$datacenter->getId(),
                            'singleClickExpand'=>true,'children'=>$aux,'contextmenu'=>$show_cluster_contextmenu);
            }
        }
        return $aux_datacenter;

    }

    /**
     * Attach device 
     *
     * @return Operation success     
     */
    public function executeJsonAddDevice(sfWebRequest $request){
        $idvendor   = $request->getParameter('idvendor');
        $idproduct  = $request->getParameter('idproduct');
        $type    = $request->getParameter('type');
        $description= $request->getParameter('description');
        $server_id  = $request->getParameter('sid');

        # Add information on local database
        $server = EtvaServerQuery::create()
            ->findPk($server_id);

        if($server->getDevices() === NULL){
            $srv_devices = array();
        }else{
            $srv_devices = json_decode($server->getDevices());
        }

        # check if device already exists
        foreach($srv_devices as $sdev){
            if($sdev->id == $idvendor.$idproduct){
                $msg_i18n = $this->getContext()->getI18N()->__('DEVICE JA ADICIONADO');
                $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
                $error = $this->setJsonError($error);
                return $this->renderText($error);
            }
        }

        # create device hash
        $device = new stdClass;
        $device->id         = $idvendor.$idproduct;   # for unicity purposes
        $device->idvendor   = $idvendor;
        $device->idproduct  = $idproduct;
        $device->type    = $type;
        $device->description= $description;
        
        # append device obj
        $srv_devices[] = $device;
        $encoded_obj = json_encode($srv_devices);
        $server->setDevices($encoded_obj);
        $server->save();

        $message = Etva::getLogMessage(array('name'=>$server->getName()), EtvaServerPeer::_OK_ADD_DEVICE_);
        $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message)));

        $response = array(
            'success'   => 'true',
            'message'   => $message,
            'info'      => 'sadasdservers'
        );

        $res = json_encode($response);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($res);
    }

    /**
     * Dettach device 
     *
     * @return Operation success     
     */
    public function executeJsonRemoveDevice(sfWebRequest $request){
        $server_id  = $request->getParameter('sid');
        $idvendor   = $request->getParameter('idvendor');
        $idproduct  = $request->getParameter('idproduct');

        # Add information on local database
        $server = EtvaServerQuery::create()
            ->findPk($server_id);

        if($server->getDevices() === NULL){
            $msg_i18n = $this->getContext()->getI18N()->__('There are no devices to remove');
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }
        
        $srv_devices = json_decode($server->getDevices());
        $new_devices = array();

        $found = false;
        
        # Found device        
        foreach($srv_devices as $sdev){
            if($sdev->idvendor.$sdev->idproduct == $idvendor.$idproduct){
                $found = true;
                continue;
            }
            
            $new_devices[] = $sdev;
        }

        $encoded_obj = json_encode($new_devices);

        $server->setDevices($encoded_obj);
        $server->save();

        if($found){
            $response = array(
                'success'   => 'true',
                'message'   => $message,
                'info'      => 'Device successfully dettached'
            );
        }else{
            $msg_i18n = $this->getContext()->getI18N()->__('Device not found');
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        $res = json_encode($response);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($res);
    }
    /**
     * check server state
     * uses checkState
     *
     * @return string json string representation     
     */
    public function executeJsonListServerDevices(sfWebRequest $request){
        $server_id = $request->getParameter('sid');
        $server = EtvaServerQuery::create()
            ->findPk($server_id);

        if($server){

            $response['success'] = true;

            if($server->getDevices() === NULL){
                $srv_devices = array();
            }else{
                $srv_devices = $server->getDevices();
                $srv_devices = json_decode($srv_devices);
            }
            $response['data'] = $srv_devices;
            $resobj = json_encode($response);

            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return $this->renderText($resobj);
        }

        return $response;
    }

    /**
     * check server state
     * uses checkState
     *
     * @return string json string representation     
     */
    public function executeJsonCheckState(sfWebRequest $request){

        $etva_server = EtvaServerPeer::retrieveByPk($request->getParameter('id'));
        $dispatcher = $request->getParameter('dispatcher');
        $response = $this->checkState($etva_server,$dispatcher);

        $success = $response['success'];

        if(!$success){
            $response['data'] = $etva_server->toArray(BasePeer::TYPE_FIELDNAME);
            $error = $this->setJsonError($response);
            return $this->renderText($error);

        }else{

            $return = json_encode($response);
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);

        }
    }

    /**
     * checks server state
     * send soap request and update DB state
     * returns response from agent
     */
    private function checkState(EtvaServer $etva_server,$dispatcher){

        $method = 'getstate';
        $response = $etva_server->soapSend($method,$dispatcher);

        $success = $response['success'];

        if(!$success){

            $etva_server->setState(0);
            $etva_server->save();

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$response['info']), EtvaServerPeer::_ERR_SOAPSTATE_);
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

        }else{

            $etva_server->setState(1);
            $etva_server->save();

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName()), EtvaServerPeer::_OK_SOAPSTATE_);
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message)));
        }

        return $response;

    }

   
    /**
     * process soap update requests of virtual machines
     *
     * $request['uuid'] //node uuid
     * $request['action'] // domains_stats (updates vms state) or domains_init (initializes agent servers)
     * $request['vms'] //list of virtual machines sent by VA
     *
     * @return array servers array on CM DB
     *
     */
    public function executeSoapUpdate(sfWebRequest $request)
    {
        if(sfConfig::get('sf_environment') == 'soap'){
           
            $action = $request->getParameter('action');

            $c = new Criteria();
            $c->add(EtvaNodePeer::UUID ,$request->getParameter('uuid'));


            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uuid'));
                $error = array('success'=>false,'error'=>$error_msg);

                //notify event log
                $node_message = Etva::getLogMessage(array('name'=>$request->getParameter('uuid')), EtvaNodePeer::_ERR_NOTFOUND_UUID_);
                $message = Etva::getLogMessage(array('info'=>$node_message), EtvaServerPeer::_ERR_SOAPUPDATE_);
                $this->dispatcher->notify(
                    new sfEvent(sfConfig::get('config_acronym'),
                            'event.log',
                            array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
                ));
                return $error;
            }

            $node_initialize = $etva_node->getInitialize();
            if($node_initialize!=EtvaNode_VA::INITIALIZE_OK)
            {
                $error_msg = sprintf('Etva node initialize status: %s', $node_initialize);
                $error = array('success'=>false,'error'=>$error_msg);

                return $error;

            }

            
            $querysrvs = EtvaServerQuery::create();
            $querysrvs->orderByPriorityHa('desc');
            $node_servers = $etva_node->getEtvaServers($querysrvs);
          
            switch($action){
                case 'domains_stats' :
                                        $vms = (array) $request->getParameter('vms');
                                        $vms_uuids = array();
                                        $vms = !empty($vms) ? (array) $vms : array();
                                        $not_affected = 0;

                                        foreach($vms as $vm)
                                            $vms_uuids[$vm->uuid] = (array) $vm;
                                        

                                        foreach($node_servers as $node_server){
                                            //error_log(sprintf('node_servers id=%s name=%s priority_ha=%s',$node_server->getId(),$node_server->getName(),$node_server->getPriorityHa()));
                                            $server_uuid = $node_server->getUuid();

                                            if(!$node_server->getUnassigned()){    // assigned only
                                                                                   // and is not migrating
                                                if ($node_server->getVmState() !== EtvaServer::STATE_MIGRATING) {
                                                    if(isset($vms_uuids[$server_uuid]))
                                                    {
                                                        $node_server->setVmState($vms_uuids[$server_uuid]['state']);
                                                        if( isset($vms_uuids[$server_uuid]['has_snapshots']) ){ // set snapshots flags
                                                            $node_server->setHassnapshots($vms_uuids[$server_uuid]['has_snapshots']);
                                                        }
                                                        $node_server->save();
                                                    }else{
                                                        $message_s = sprintf('Node %s could not check state for virtual machine %s(%s)', $etva_node->getName(),$node_server->getName(),$server_uuid);
                                                        $not_affected++;
                                                        $this->dispatcher->notify(
                                                            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                                                                array('message' => $message_s,'priority'=>EtvaEventLogger::ERR)));
                                                        error_log($message_s);
                                                    }
                                                } else {
                                                    $message_s = sprintf('Node %s could not check state for virtual machine %s(%s) beacuse is migrating', $etva_node->getName(),$node_server->getName(),$server_uuid);
                                                    error_log($message_s);
                                                }
                                            }
                                        }

                                        // update free memory
                                        $etva_node->updateMemFree();
                                        $etva_node->save();

                                        //notify system log
                                                                                
                                        if($not_affected > 0){
                                            $message = sprintf('Node %s could not check for %d virtual machine(s) state', $etva_node->getName(),$not_affected);
                                            $this->dispatcher->notify(
                                                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                                                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
                                            return array('success'=>false, 'reason'=>'_servers_inconsistency_' );
                                        }
                                        return array('success'=>true);

                                        break;
                case 'domains_gainfo' :
                                        $vms = (array) $request->getParameter('vms');
                                        $vms_uuids = array();
                                        $vms = !empty($vms) ? (array) $vms : array();

                                        foreach($vms as $vm)
                                            $vms_uuids[$vm->uuid] = (array) $vm;

                                        foreach($node_servers as $node_server){
                                            $server_uuid = $node_server->getUuid();

                                            if( !$node_server->getUnassigned() ){   // assigned only
                                                if(isset($vms_uuids[$server_uuid])){
                                                    //$str = json_encode($vms_uuids[$server_uuid]['msg']);
                                                    $str = $vms_uuids[$server_uuid]['msg'];
                                                    $obj = json_decode($str);

                                                    $node_server->mergeGaInfo($str);    // merge GA info
                                                    $node_server->resetHeartbeat(EtvaServerPeer::_GA_RUNNING_);
                                                    $node_server->setHbnrestarts(0);   // reset num of restarts
                                                    $node_server->save();

                                                    $message = sprintf('domains_gainfo guest agent info updated (id=%s name=%s type=%s hb=%s)',$node_server->getId(),$node_server->getName(),$obj->__name__,$node_server->getHeartbeat());
                                                    error_log($message);
                                                    /*$this->dispatcher->notify(
                                                        new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                                                            array('message' => $message,'priority'=>EtvaEventLogger::INFO)));*/
                                                }
                                            }
                                        }

                                        return array('success'=>true);
                                        break;
                case 'domains_sync' :
                                        $new_servers = array();

                                        $vms = (array) $request->getParameter('vms');
                                        $vms_uuids = array();
                                        $vms = !empty($vms) ? (array) $vms : array();
                                        $not_affected = 0;

                                        foreach($vms as $vm){
                                            if( $vm ) $vms_uuids[$vm->uuid] = (array) $vm;
                                        }

                                        foreach($node_servers as $node_server)
                                        {
                                            error_log(sprintf('domains_sync server id=%s name=%s priority_ha=%s',$node_server->getId(),$node_server->getName(),$node_server->getPriorityHa()));
                                            if( !$node_server->getUnassigned() ){ // ignore unassigned servers
                                                $server_name = $node_server->getName();
                                                $new_servers[$server_name] = $node_server->_VA();
                                                
                                                $server_uuid = $node_server->getUuid();
                                                if( isset($vms_uuids[$server_uuid]) ){
                                                    if( ($vms_uuids[$server_uuid]['state']!=EtvaServer::RUNNING) && (($node_server->getVmState()==EtvaServer::RUNNING) || $node_server->getAutostart()) ){
                                                        error_log(sprintf('domains_sync server id=%s name=%s should be running',$node_server->getId(),$node_server->getName()));
                                                        $node_server->setHblaststart('NOW');    // update hb last start
                                                        $node_server->save();
                                                    }
                                                    unset($vms_uuids[$server_uuid]);
                                                }
                                            }
                                        }

                                        $servers_names = array_keys($new_servers);
                                        $servers = implode(', ',$servers_names);

                                        /*
                                         * check if is an appliance restore operation...
                                         */
                                        $apli = new Appliance();
                                        $action = $apli->getStage(Appliance::RESTORE_STAGE);
                                        if($action) $apli->setStage(Appliance::RESTORE_STAGE,Appliance::VA_UPDATE_VMS);

                                        //notify system log
                                        if($new_servers) $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$etva_node->getName(),'info'=>$servers), EtvaServerPeer::_OK_SOAPUPDATE_),'priority'=>EtvaEventLogger::INFO)));

                                        $load_servers = count($new_servers) ? array_values($new_servers) : array();
                                        $destroy_servers = count($vms_uuids) ? array_values($vms_uuids) : array();

                                        $return = array('success'=>true, 'load_servers'=>$load_servers, 'destroy_servers'=>$destroy_servers);
                                        return $return;

                                        break;
                default              :
                                        $new_servers =array();
                                        foreach($node_servers as $node_server)
                                        {
                                            if( !$node_server->getUnassigned() ){ // ignore unassigned servers
                                                $server_name = $node_server->getName();
                                                $new_servers[$server_name] = $node_server->_VA();
                                            }
                                        }

                                        $servers_names = array_keys($new_servers);
                                        $servers = implode(', ',$servers_names);

                                        /*
                                         * check if is an appliance restore operation...
                                         */
                                        $apli = new Appliance();
                                        $action = $apli->getStage(Appliance::RESTORE_STAGE);
                                        if($action) $apli->setStage(Appliance::RESTORE_STAGE,Appliance::VA_UPDATE_VMS);

                                        //notify system log
                                        if($new_servers) $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$etva_node->getName(),'info'=>$servers), EtvaServerPeer::_OK_SOAPUPDATE_),'priority'=>EtvaEventLogger::INFO)));

                                        return $new_servers;
            }                                         

        }
    }

    /**
     *
     *
     * List server snapshots
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['nid'] = $id; //node destination ID
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     * @return string json string representation of array('success'=>true,'agent'=>$agent, 'response'=>$response)
     *
     */
    public function executeJsonListSnapshots(sfWebRequest $request)
    {
        $sid = $request->getParameter('id');

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$server_log), EtvaServerPeer::_ERR_MIGRATE_UNKNOWN_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $etva_node = $etva_server->getEtvaNode();

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->get_list_snapshots($etva_node);

        if(isset($response['success']) && !$response['success']){
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

        $elements = $response['response'];

        // return array
        $result = array('success'=>true,
                    'total'=> count($elements),
                    'data'=> $elements,
                    'agent'=>$etva_node->getName()
        );

        $return = json_encode($result);

        if(sfConfig::get('sf_environment') == 'soap') return $return;

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($return);
    }
    public function executeJsonCreateSnapshot(sfWebRequest $request)
    {
        if( $sid = $request->getParameter('uuid') ){
            $etva_server = EtvaServerPeer::retrieveByUuid($sid);
        } else {
            $sid = $request->getParameter('id');
            $etva_server = EtvaServerPeer::retrieveByPK($sid);
        }

        if(!$etva_server){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$server_log), EtvaServerPeer::_ERR_MIGRATE_UNKNOWN_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $etva_node = $etva_server->getEtvaNode();

        $snapshot = $request->getParameter('snapshot');

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->create_snapshot($etva_node,$snapshot);

        if($response['success']){
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);
        }else{
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }
    }
    public function executeJsonRevertSnapshot(sfWebRequest $request)
    {
        $sid = $request->getParameter('id');

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$server_log), EtvaServerPeer::_ERR_MIGRATE_UNKNOWN_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $etva_node = $etva_server->getEtvaNode();

        $snapshot = $request->getParameter('snapshot');

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->revert_snapshot($etva_node,$snapshot);

        if($response['success']){
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);
        }else{
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }
    }
    public function executeJsonRemoveSnapshot(sfWebRequest $request)
    {
        $sid = $request->getParameter('id');

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>sfConfig::get('config_acronym'),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('info'=>$server_log), EtvaServerPeer::_ERR_MIGRATE_UNKNOWN_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $etva_node = $etva_server->getEtvaNode();

        $snapshot = $request->getParameter('snapshot');

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->remove_snapshot($etva_node,$snapshot);

        if($response['success']){
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);
        }else{
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }
    }
    /**
     *
     *
     * Download server backup from snapshot
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['snapshot'] = $snapshot; //using snapshot
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     * donwload machine in OVF format
     *
     */
    public function executeDownloadBackupSnapshot(sfWebRequest $request)
    {

        if( $sid = $request->getParameter('uuid') ){
            $etva_server = EtvaServerPeer::retrieveByUuid($sid);
        } else if( $sid = $request->getParameter('name') ){
            $etva_server = EtvaServerPeer::retrieveByName($sid);
        } else {
            $sid = $request->getParameter('id');
            $etva_server = EtvaServerPeer::retrieveByPK($sid);
        }

        /*if(!$etva_server){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));            
            return $this->renderText($msg_i18n);
        }

        $newsnapshot = $request->getParameter('newsnapshot');
        $snapshot = $request->getParameter('snapshot');
        $delete = $request->getParameter('delete');

        if(!$etva_server->getHasSnapshots() && !$snapshot && !$newsnapshot && ($etva_server->getVmState() != 'stop') && ($etva_server->getVmState() != 'notrunning') ){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));            
            return $this->renderText($msg_i18n);
        }
        
        $etva_node = $etva_server->getEtvaNode();
        
        $server_va = new EtvaServer_VA($etva_server);

        if( !$etva_server->getHasSnapshots() || $newsnapshot ){
            $response = $server_va->create_snapshot($etva_node,$newsnapshot);
            if( !$response['success'] ){
                $msg_i18n = $response['info'];
                return $this->renderText($msg_i18n);
            }
        }

        $url = "http://".$etva_node->getIp();
        $request_body = "uuid=".$etva_server->getUuid();

        if( $snapshot ){
            $request_body .= "&snapshot=$snapshot";
        }

        $filename = $etva_server->getName().".tar";
        
        $port = $etva_node->getPort();
        if($port) $url.=":".$port;        
        $url.="/vm_backup_snapshot_may_fork";*/
        
        /*
         * get response stream data
         */
        /*$ovf_curl = new ovfcURL($url);
        $ovf_curl->post($request_body);
        $ovf_curl->setFilename($filename);
        $ovf_curl->exec();

        if($ovf_curl->getStatus()==500) return $this->renderText('Error 500');*/

        $options_task_server_backup = array( // options
                                            'filepath'=>'STDOUT'
                                        );

        $snapshot = $request->getParameter('snapshot');
        if( $snapshot ){
            $options_task_server_backup['snapshot'] = $snapshot;
        }
        $newsnapshot = $request->getParameter('newsnapshot');
        if( $newsnapshot ){
            $options_task_server_backup['newsnapshot'] = $newsnapshot;
        }
        $delete = $request->getParameter('delete');
        if( $delete && ($delete!='false') ){ // delete after
            $options_task_server_backup['deletesnapshot'] = true;
        }

        $task_server_backup = new serverBackupTask($this->dispatcher, new sfFormatter());
        $res_task = $task_server_backup->run(
                                    array( // arguments
                                        'serverid'=>$sid
                                    ),
                                    $options_task_server_backup
                                );
        if( $res_task < 0 ){
            // TODO treat error
            return $this->renderText('Error 500');
        }

        return sfView::NONE;
    }
}
