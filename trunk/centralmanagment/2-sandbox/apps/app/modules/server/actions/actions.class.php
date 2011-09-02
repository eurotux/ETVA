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

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('agent'=>'ETVA','success'=>false,'error'=>$msg_i18n);

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
            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $etva_node = $etva_server->getEtvaNode();        

        $server_array = $etva_server->toArray(BasePeer::TYPE_FIELDNAME);
        $server_array['node_ncpus'] = $etva_node->getCputotal();
        $server_array['node_state'] = $etva_node->getState();
        $server_array['node_maxmemory'] = $etva_node->getMemfree();

        $all_shared = $etva_server->isAllSharedLogicalvolumes();
        $server_array['all_shared_disks'] = $all_shared;


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
            $error = array('agent'=>'ETVA','success'=>false,'error'=>$msg_i18n);

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

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$node_log), EtvaServerPeer::_ERR_MIGRATE_ );
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $from_etva_node = $etva_server->getEtvaNode();        

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_migrate($from_etva_node, $to_etva_node);        

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
            $error = array('agent'=>'ETVA','success'=>false,'error'=>$msg_i18n);

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

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$node_log), EtvaServerPeer::_ERR_MOVE_ );
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
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
        if($disk_vgs) $disk_vg = $disk_vgs[0]->getEtvaVolumegroup();

        if(!$disk_vg) return sfView::NONE;
        
        $this->max_size_diskfile = $disk_vg->getFreesize();

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
   /*
    * create virtual machine
    * sends soap request and stores info
    */
    public function executeJsonCreate(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');
        $server = json_decode($request->getParameter('server'),true);

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$server['name'],'info'=>$node_log), EtvaServerPeer::_ERR_CREATE_);
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
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
            $error = array('agent'=>'ETVA','success'=>false,'error'=>$msg_i18n);

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
        $method = 'start_vm';
        $virtAgentID = $request->getParameter('nid');
        $server = $request->getParameter('server');
        return $this->processStartStop($virtAgentID, $server, $method);
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
        $method = 'vmStop';
        $virtAgentID = $request->getParameter('nid');
        $server = $request->getParameter('server');
        return $this->processStartStop($virtAgentID, $server, $method);
    }

    protected function processStartStop($nid, $server, $method)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$server,'info'=>$node_log), $method == 'vmStop' ? EtvaServerPeer::_ERR_STOP_ : EtvaServerPeer::_ERR_START_ );
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));



            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$etva_server = $etva_node->retrieveServerByName($server)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_,array('%name%'=>$server));
            $error = array('agent'=>$etva_node->getName(),'success'=>false,'error'=>$msg_i18n);

            //notify event log
            $server_log = Etva::getLogMessage(array('name'=>$server), EtvaServerPeer::_ERR_NOTFOUND_);
            $message = Etva::getLogMessage(array('name'=>$server,'info'=>$server_log), $method == 'vmStop' ? EtvaServerPeer::_ERR_STOP_ : EtvaServerPeer::_ERR_START_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $params = array('uuid'=>$etva_server->getUuid());

        switch($method){
            case 'start_vm':
                            $boot = $etva_server->getBoot();
                            $location = $etva_server->getLocation();
                            $vm_type = $etva_server->getVmType();
                            $first_boot = $etva_server->getFirstBoot();

                            if($first_boot){
                                $params['first_boot'] = $first_boot;
                                if($location && $vm_type=='pv') $boot = 'location';
                            }

                            $params['boot'] = $boot;
                            if($boot=='location' || $boot=='cdrom') $params['location'] = $location;
                            $params['vnc_keymap'] = $etva_server->getVncKeymap();
                            break;
              case 'vmStop':
                    default:
                            break;
        }

        $response = $etva_node->soapSend($method,$params);

        if(!$response['success']){

            $result = $response;
            $msg_i18n = $this->getContext()->getI18N()->__($method == 'vmStop' ? EtvaServerPeer::_ERR_STOP_ : EtvaServerPeer::_ERR_START_,array('%name%'=>$server,'%info%'=>$response['error']));
            $result['error'] = $msg_i18n;

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$server,'info'=>$response['info']), $method == 'vmStop' ? EtvaServerPeer::_ERR_STOP_ : EtvaServerPeer::_ERR_START_);
            $this->dispatcher->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }

        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];
        $returned_object = (array) $response_decoded['_obj_'];

        // get some info from response...

        //update some server data from agent response
        $etva_server->initData($returned_object);
        $etva_server->setFirstBoot(0);
        
        if($first_boot) $etva_server->setBoot('filesystem');
        else{

            $boot_field = $etva_server->getBoot();

            switch($boot_field){
                case 'filesystem' :
                case 'pxe'        :
                                    if(!$etva_server->getCdrom()) $etva_server->setLocation('');
                                    break;
            }
        }

        $etva_server->save();

        $msg_i18n = $this->getContext()->getI18N()->__($method == 'vmStop' ? EtvaServerPeer::_OK_STOP_ : EtvaServerPeer::_OK_START_,array('%name%'=>$server));        

        $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);

        //notify event log
        $message = Etva::getLogMessage(array('name'=>$server), $method == 'vmStop' ? EtvaServerPeer::_OK_STOP_ : EtvaServerPeer::_OK_START_);
        $this->dispatcher->notify(
            new sfEvent($response['agent'], 'event.log',
                array('message' => $message)));

        $return = json_encode($result);

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;
        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);

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
                new sfEvent('ETVA', 'event.log',
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
        $this->pager = new sfPropelPager('EtvaServer', $limit);
        $c = new Criteria();

        $this->addSortCriteria($c);
        //$this->addNodeCriteria($c);

        //$etva_node = EtvaNodePeer::retrieveByPK($this->getRequestParameter("nid"));

        $this->pager->setCriteria($c);
        $this->pager->setPage($page);

        $this->pager->setPeerMethod('doSelectJoinEtvaNode');
        $this->pager->setPeerCountMethod('doCountJoinEtvaNode');

        $this->pager->init();


        $elements = array();
        $i = 0;

        # Get data from Pager
        foreach($this->pager->getResults() as $item){            
            $etva_node = $item->getEtvaNode();
            $elements[$i] = $item->toArray();
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
        $this->pager = new sfPropelPager('EtvaServer', $limit);
        $c = new Criteria();

        $this->addSortCriteria($c);

        $nodeID = $this->getRequestParameter("nid");
        $c->add(EtvaServerPeer::NODE_ID, $nodeID);

        $etva_node = EtvaNodePeer::retrieveByPK($this->getRequestParameter("nid"));
        if(!$etva_node) return array('success'=>false);        

        $this->pager->setCriteria($c);
        $this->pager->setPage($page);

        //$this->pager->setPeerMethod('doSelectJoinsfGuardGroup');
        $this->pager->setPeerMethod('doSelectJoinAll');
        //$this->pager->setPeerCountMethod('doCountJoinsfGuardGroup');
        $this->pager->setPeerCountMethod('doCountJoinAll');

        $this->pager->init();


        $elements = array();
        $i = 0;

        # Get data from Pager
        foreach($this->pager->getResults() as $item){            
            $elements[$i] = $item->toDisplay();
            $etva_vnc_ports = $item->getEtvaVncPorts();
            if(count($etva_vnc_ports) > 0){
                $etva_vnc_port = $etva_vnc_ports[0];
                $elements[$i]['vnc_port'] = $etva_vnc_port->getId();                
            }
            $all_shared = $item->isAllSharedLogicalvolumes();
            $elements[$i]['all_shared_disks'] = $all_shared;
            $i++;
        }


        $final = array(
            'total' => $this->pager->getNbResults(),
            'node_state'=>$etva_node->getState(),
            'node_initialize'=>$etva_node->getInitialize(),            
            'data'  => $elements
        );

        $c = new Criteria();
        $c->add(EtvaVolumegroupPeer::VG,sfConfig::get('app_volgroup_disk_flag'));
        $disk_vgs = $etva_node->getEtvaNodeVolumegroupsJoinEtvaVolumegroup($c);
        if(!$disk_vgs) $final['node_has_vgs'] = 0;
        else $final['node_has_vgs'] = 1;
        
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
        $nodes = EtvaNodePeer::getWithServers();

        $aux = array();
        foreach ($nodes as $node){

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

            if(!$state){
                $cls_node = 'no-active';
                $node_qtip = $this->getContext()->getI18N()->__(EtvaNodePeer::_STATE_DOWN_,array('%name%'=>$node_name));
            }

            $last_message = $node->getLastMessage();
            $iconCls = '';
     
            $message_decoded = json_decode($last_message,true);

            switch($message_decoded['priority']){
                case EtvaEventLogger::ERR : $iconCls = 'icon-error';
                                            break;
            }

            if($message_decoded['message']) $node_qtip = $this->getContext()->getI18N()->__($message_decoded['message']);

            $aux_servers = array();
            foreach ($node->getServers() as $i => $server){
                $state_server = $server->getState();

                $agent_server_port = $server->getAgentPort();
                $agent_tmpl = $server->getAgentTmpl();
                $vm_state = $server->getVmState();

                $cls_server = 'no-active';

                if($vm_state=='running')
                {
                    if($agent_server_port)
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
                $aux_servers[] = array('text'=>$server->getName(),'type'=>'server','id'=>$child_id,'state'=>$state_server,'agent_tmpl'=>$agent_tmpl,'cls'=>$cls_server,'url'=> $this->getController()->genUrl('server/view?id='.$server->getID()),
                            'leaf'=>true);
            }



            if(empty($aux_servers)){
                $aux[] = array('text'=>$node_name,'type'=>'node','iconCls'=>$iconCls,'state'=>$state,'id'=>$node->getID(),'initialize'=>$initialize,'url'=>$this->getController()->genUrl('node/view?id='.$node->getID()),
                'children'=>$aux_servers,'expanded'=>true,'qtip'=>$node_qtip,'cls'=> 'x-tree-node-collapsed '.$cls_node);
            }else $aux[] = array('text'=>$node_name,'type'=>'node','iconCls'=>$iconCls,'state'=>$state,'id'=>$node->getID(),'initialize'=>$initialize,'cls'=>$cls_node,'url'=>$this->getController()->genUrl('node/view?id='.$node->getID()),
                        'singleClickExpand'=>true,'qtip'=>$node_qtip,'children'=>$aux_servers);

        }
        return $aux;

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
            $this->dispatcher->notify(new sfEvent('ETVA', 'event.log', array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

        }else{

            $etva_server->setState(1);
            $etva_server->save();

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName()), EtvaServerPeer::_OK_SOAPSTATE_);
            $this->dispatcher->notify(new sfEvent('ETVA', 'event.log', array('message' => $message)));
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
                    new sfEvent('ETVA',
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

            $node_servers = $etva_node->getEtvaServers();

            switch($action){
                case 'domains_stats' :
                                        $vms = (array) $request->getParameter('vms');
                                        $vms_uuids = array();
                                        $vms = !empty($vms) ? (array) $vms : array();
                                        $not_affected = 0;

                                        foreach($vms as $vm)
                                            $vms_uuids[$vm->uuid] = (array) $vm;
                                        

                                        foreach($node_servers as $node_server){
                                            $server_uuid = $node_server->getUuid();

                                            if(isset($vms_uuids[$server_uuid]))
                                            {
                                                $node_server->setVmState($vms_uuids[$server_uuid]['state']);
                                                $node_server->save();
                                            }else $not_affected++;
                                        }

                                        //notify system log
                                                                                
                                        $message = sprintf('Node %s could not check for %d virtual machine(s) state', $etva_node->getName(),$not_affected);

                                        if($not_affected > 0)
                                            $this->dispatcher->notify(
                                                new sfEvent('ETVA', 'event.log',
                                                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
                                        
                                        
                                        return array('success'=>true);

                                        break;
                default              :


                                        $new_servers =array();
                                        foreach($node_servers as $node_server)
                                        {
                                            $server_name = $node_server->getName();
                                            $new_servers[$server_name] = $node_server->_VA();
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
                                        if($new_servers) $this->dispatcher->notify(new sfEvent('ETVA', 'event.log', array('message' => Etva::getLogMessage(array('name'=>$etva_node->getName(),'info'=>$servers), EtvaServerPeer::_OK_SOAPUPDATE_),'priority'=>EtvaEventLogger::INFO)));

                                        return $new_servers;
            }                                         

        }
    }


}
