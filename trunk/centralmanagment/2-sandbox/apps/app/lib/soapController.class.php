<?php

class soapController extends sfController {


    public function __construct(){
        /*
         * Since we're bypassing Symfony's action dispatcher, we have to initialize manually.
         */
        $this->context = sfContext::getInstance();
        $this->request = $this->context->getRequest();

        $this->__typedef['TupleNode'] = array('success'=>'boolean','uuid'=>'string');

        $this->__typedef['ArrayOfStrings'] = array(array('item'=>'string'));

        $this->__typedef['VirtAgentUpdateParams'] = array('ip'=>'string', 'state'=>'integer');

        $this->__typedef['VirtAgentInitParams'] = array('name'=>'string',
                                                        'memtotal'=>'long',
                                                        'memfree'=>'long',
                                                        'storagedir'=>'string',
                                                        'cputotal'=>'integer',
                                                        'netcards'=>'integer',
                                                        'ip'=>'string',
                                                        'port'=>'integer',
                                                        'uuid'=>'string',
                                                        'hypervisor'=>'string',
                                                        'state'=>'integer');

        $this->__typedef['ArraySuccess'] = array('success'=>'boolean');

        /*
         * Initialize virtual agent data in DB
         * @params
         * array('name'=>$in->name
         *       'memtotal'=>$in->memtotal,
         *       'memfree'=>$in->memfree,
         *       'cputotal'=>$in->cputotal,
         *       'network_cards'=>$in->netcards,
         *       'ip'=>$in->ip,
         *       'port'=>$in->port,
         *       'uuid'=>$in->uuid,
         *       'storagedir'=>$in->storagedir,
         *       'hypervisor'=>$in->hypervisor,
         *       'state'=>$in->state);
         *
         */
        $this->__dispatch_map['initializeVirtAgent'] = array(
                                                     'in'    => array('array'=>'{urn:soapController}VirtAgentInitParams'),
                                                     'out'   => array('return'=>"{urn:soapController}TupleNode")
        );
        
        $this->__dispatch_map['clearVirtAgent'] = array(
                                                     'in'    => array('uuid'=>'string','error'=>'{urn:soapController}ArrayOfStrings'),
                                                     'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        $this->__dispatch_map['restoreVirtAgent'] = array(
                                                     'in'    => array('uuid'=>'string','ok'=>'{urn:soapController}ArrayOfStrings'),
                                                     'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        /*
         * Updates DB field data for an specified node
         */
        $this->__dispatch_map['updateVirtAgent'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'data'=>'{urn:soapController}VirtAgentUpdateParams'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        /*
         * Return vlans that are on DB
         */
        $this->__dispatch_map['updateVirtAgentVlans'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'vlans'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>'{urn:soapController}ArrayOfStrings')
        );

        /*
         * Updates DB info about node devices
         *
         */
        $this->__dispatch_map['updateVirtAgentDevices'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'devs'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        /*
         * Updates DB info about node physical volumes
         *
         */
        $this->__dispatch_map['updateVirtAgentPvs'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'pvs'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        /*
         * Updates DB info about node logical volumes
         *
         */
        $this->__dispatch_map['updateVirtAgentLvs'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'lvs'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        /*
         * Updates DB info about node volume groups
         *
         */
        $this->__dispatch_map['updateVirtAgentVgs'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'vgs'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        /*
         * Return virtual machines (servers) that are on DB
         *
         */
        $this->__dispatch_map['updateVirtAgentServers'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'vms'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        /*
         * updates statistics logs RRA files
         */
        $this->__dispatch_map['updateVirtAgentLogs'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'data'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        /*
         * Initialize virtual server services
         *
         */
        $this->__dispatch_map['initAgentServices'] = array(
                                                        'in'    => array(
                                                                         'name'=>'string',
                                                                         'ip'=>'string',
                                                                         'port'=>'integer',
                                                                        'macaddr'=>'string',
                                                                        'services'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );
        
        
        $this->__dispatch_map['restoreManagAgent'] = array(
                                                     'in'    => array('macaddr'=>'string','ok'=>'{urn:soapController}ArrayOfStrings'),
                                                     'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );        


        $this->__dispatch_map['updateVirtAgentServersStats'] = array(
                                                        'in'    => array('uuid'=>'string',
                                                                        'vms'=>'{urn:soapController}ArrayOfStrings'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );



    }




    protected function soapAuth($domain,$password){
        try {

            $c = new Criteria();
            $c->add(UserPeer::USERNAME ,$domain);
            $c->add(UserPeer::PASSWORD ,$password);

            $check = UserPeer::doSelectOne($c);

            if($check){
                $user = $this->context->getUser();
                $user->addCredential($check->getCredential());
                $user->setAuthenticated(true);
            }else{
                throw new Exception('Soap Authentication failed');
            }
        }catch (Exception $e){
            throw new SoapFault("1",$e->getMessage());
        }
    }

    /*
     * if this method invoked means that MA restore OK
     */
    function restoreManagAgent($macaddr, $ok)
    {
        $data_array = array('macaddr'=>$macaddr, 'ok'=>$ok);

        $request_array = array('request'=>array_merge($data_array,array('method'=>'restoreManagAgent')));

        $this->request->setParameter('macaddr', $data_array['macaddr']);
        $this->request->setParameter('ok', $data_array['ok']);

        $action = $this->getAction('service','soapRestore');
        $result = $action->executeSoapRestore($this->request);

        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));

        return $result;

    }

    /*
     * if this method invoked means VA restore success
     */
    function restoreVirtAgent($uuid, $ok)
    {
        $node_array = array('uuid'=>$uuid, 'ok'=>$ok);

        $request_array = array('request'=>array_merge($node_array,array('method'=>'restoreVirtAgent')));

        $this->request->setParameter('node_uuid', $node_array['uuid']);
        $this->request->setParameter('ok', $node_array['ok']);

        $action = $this->getAction('node','soapRestore');
        $result = $action->executeSoapRestore($this->request);

        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));

        return $result;
        

    }


   /*
    * clears virtagent from DB
    * if receiving this means that agent could not perform operations. Reset DB data. and initialize agent.
    */
    function clearVirtAgent($uuid,$error)
    {
        $node_array = array('uuid'=>$uuid, 'error'=>$error);
        
        $request_array = array('request'=>array_merge($node_array,array('method'=>'clearVirtAgent')));        
        
        $this->request->setParameter('node_uuid', $node_array['uuid']);
        $this->request->setParameter('error', $node_array['error']);

        $action = $this->getAction('node','soapClear');
        
        $result = $action->executeSoapClear($this->request);        
        
        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));

        return $result;

    }


   /*
    * receives initialization data from node virt agent and store it in db
    */
    function initializeVirtAgent($data){

        $node_array = array(
                'name'=>$data->name,
                'memtotal'=>$data->memtotal,                
                'cputotal'=>$data->cputotal,
                'network_cards'=>$data->netcards,
                'ip'=>$data->ip,
                'port'=>$data->port,
                'uuid'=>$data->uuid,
                'storagedir'=>$data->storagedir,
                'hypervisor'=>$data->hypervisor,
                'state'=>$data->state);

        $request_array = array('request'=>array_merge($node_array,array('method'=>'initializeVirtAgent')));

        $this->request->setParameter('etva_node', $node_array);

        $action = $this->getAction('node','soapCreate');
        $result = $action->executeSoapCreate($this->request);

        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));

        return $result;

    }

    function updateVirtAgent($node_uuid, $node_data)
    {

        $node_array = array(
                'ip'=>$node_data->ip,
                'state'=>$node_data->state);


        $request_array = array('request'=>array_merge($node_array,array('uuid' => $node_uuid, 'method'=>'updateVirtAgent')));        

        $this->request->setParameter('uuid', $node_uuid);
        $this->request->setParameter('data',$node_array);

        $action = $this->getAction('node','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));

        return $result;
    }

    function updateVirtAgentServers($node_uuid, $vms)
    {

        $this->request->setParameter('uuid', $node_uuid);
        $this->request->setParameter('action', 'domains_init');
        $this->request->setParameter('vms',$vms);

        $data = $this->request->getParameterHolder();
        $all_data = $data->getAll();
        $all_data['module'] = 'server';
        $all_data['action'] = 'soapUpdate';
        $all_data['method'] = 'updateVirtAgentServers';

        $request_array = array('request'=>$all_data);

        $action = $this->getAction('server','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        $response_array = array('response'=>$result);
        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));


        return $result;

    }


    function updateVirtAgentServersStats($node_uuid, $vms)
    {

        $this->request->setParameter('uuid', $node_uuid);
        $this->request->setParameter('action', 'domains_stats');
        $this->request->setParameter('vms',$vms);

        $data = $this->request->getParameterHolder();
        $all_data = $data->getAll();
        $all_data['module'] = 'server';
        $all_data['action'] = 'soapUpdate';
        $all_data['method'] = 'updateVirtAgentServersStats';

        $request_array = array('request'=>$all_data);

        $action = $this->getAction('server','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        $response_array = array('response'=>$result);
        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));


        return $result;

    }


    function updateVirtAgentVlans($node_uuid, $vlans)
    {
        $this->request->setParameter('uuid', $node_uuid);
        $this->request->setParameter('vlans',$vlans);

        $data = $this->request->getParameterHolder();
        $send_data = $data->getAll();
        $send_data['module'] = 'vlan';
        $send_data['method'] = 'updateVirtAgentVlans';
        $send_data['action'] = 'soapUpdate';

        $request_array = array('request'=>$send_data);


        $action = $this->getAction('vlan','soapUpdate');
        $response = $action->executeSoapUpdate($this->request);

        $response_array = array('response'=>$response);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));

        return $response;
    }


    function updateVirtAgentDevices($node_uuid, $devs)
    {

        $this->request->setParameter('uuid', $node_uuid);
        $this->request->setParameter('devs',$devs);

        $data = $this->request->getParameterHolder();
        $all_data = $data->getAll();
        $all_data['module'] = 'physicalvol';
        $all_data['action'] = 'soapUpdate';
        $all_data['method'] = 'updateVirtAgentDevices';

        $request_array = array('request'=>$all_data);

        $action = $this->getAction('physicalvol','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'), $all_params));

        return $result;
    }

    function updateVirtAgentPvs($node_uuid, $lvs)
    {


        //    $this->request->setParameter('uid', $node_uuid);
        //    $this->request->setParameter('lvs',$lvs);
        //
        //    $action = $this->getAction('logicalvol','soapUpdate');
        //    $result = $action->executeSoapUpdate($this->request);
        $result = array('success'=>true);
        return $result;
    }




    function updateVirtAgentLvs($node_uuid, $lvs)
    {
        $this->request->setParameter('uuid', $node_uuid);
        $this->request->setParameter('lvs',$lvs);

        $data = $this->request->getParameterHolder();
        $all_data = $data->getAll();
        $all_data['module'] = 'logicalvol';
        $all_data['action'] = 'soapUpdate';
        $all_data['method'] = 'updateVirtAgentLvs';

        $request_array = array('request'=>$all_data);

        $action = $this->getAction('logicalvol','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'), $all_params));

        return $result;
    }


    function updateVirtAgentVgs($node_uuid, $vgs)
    {

        $dispatcher = sfContext::getInstance()->getEventDispatcher();

        $this->request->setParameter('uuid', $node_uuid);
        $this->request->setParameter('vgs',$vgs);

        $data = $this->request->getParameterHolder();
        $all_data = $data->getAll();
        $all_data['module'] = 'volgroup';
        $all_data['action'] = 'soapUpdate';
        $all_data['method'] = 'updateVirtAgentVgs';

        $action = $this->getAction('volgroup','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        $request_array = array('request'=>$all_data);
        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        //log to file soap message
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));

        return $result;
    }


    function updateVirtAgentLogs($node_uuid,$data)
    {

        $c = new Criteria();
        $c->add(EtvaNodePeer::UUID ,$node_uuid);
        $return = 0;

        $etva_node = EtvaNodePeer::doSelectOne($c);

        if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
            $error_msg = sprintf('Object etva_node does not exist (%s).', $node_uuid);
            $error = array('success'=>false,'error'=>$error_msg);
            return $error;
        }

        $node_initialize = $etva_node->getInitialize();
        if($node_initialize!=EtvaNode_VA::INITIALIZE_OK)
        {
            $error_msg = sprintf('Etva node initialize status: %s', $node_initialize);
            $error = array('success'=>false,'error'=>$error_msg);

            return $error;

        }

        $node_uuid = $etva_node->getUuid();


        $cpu_load = $data->load;
        $cpu_load_sort = array($cpu_load->onemin,
            $cpu_load->fivemin,
            $cpu_load->fifteenmin
        );

        $cpu_load = new NodeLoadRRA($node_uuid);
        $cpu_load->update($cpu_load_sort);

        $virtServers = $data->virtualmachines;


        if($virtServers)
            foreach($virtServers as $server_name => $server_data){

                if($etva_server = $etva_node->retrieveServerByName($server_name)){


                /*
                 * store network info in RRA file
                 */
                $server_network_data = $server_data->network;
                /*
                 * create file per network device
                 */
                foreach($server_network_data as $intfname=>$intfdata){

                    $macaddr = $intfdata->macaddr;
                    $etva_network = $etva_server->retrieveByMac($macaddr);
                    if($etva_network){
                        $target = $etva_network->getTarget();


                        // if target not in network table of the DB
                        if($target!=$intfname){
                            $etva_network->updateTarget($intfname);
                        }


                        $tx = $intfdata->tx_bytes;
                        $rx = $intfdata->rx_bytes;

                        $intf_sort = array($rx,$tx);

                        $mac_strip = str_replace(':','',$macaddr);
                        // create log file
                        $server_network_rra = new ServerNetworkRRA($node_uuid,$etva_server->getUuid(),$mac_strip);
                        //send/update file information
                        $return = $server_network_rra->update($intf_sort);
                    }


                }// end server networks


                /*
                 * disk stuff
                 */
                if(isset($server_data->disk)){
                    $server_disk_data = $server_data->disk;

                    foreach($server_disk_data as $disk=>$diskdata){
                        
                        $diskname = $diskdata->name;

                        $read_b = $diskdata->rd_bytes;
                        $write_b = $diskdata->wr_bytes;
                        $disk_sort = array($read_b,$write_b);

                        // create log file
                        $server_disk_rw_log = new ServerDisk_rwRRA($node_uuid,$etva_server->getUuid(),$diskname);
                        //send/update file information
                        $return = $server_disk_rw_log->update($disk_sort);

                        /*
                         * DISK SPACE RRA NOT USED ANYMORE
                         */
                        //$size = $diskdata->size;
                        //$freesize = $diskdata->freesize;
                        //$disk_space = array($size,$freesize);
                        //$server_disk_spaceRRA = new ServerDiskSpaceRRA($node_uuid,$etva_server->getUuid(),$diskname);
                        //$return = $server_disk_spaceRRA->update($disk_space);



                    }// end server disk
                }

                // store cpu utilization
                $server_cpu_data = $server_data->cpu;
                $server_cpu_per = array($server_cpu_data->cpu_per);

          //      $logger->log("cpu ".$server_cpu_data->cpu_per);

                // create log file
                $server_cpu_per_rra = new ServerCpuUsageRRA($node_uuid,$etva_server->getUuid());
                //send/update file information
                $return = $server_cpu_per_rra->update($server_cpu_per);


                /*
                 * store memory info
                 */

                // store memory utilization
                $server_memory_data = $server_data->memory;

                $server_memory_per = array($server_memory_data->mem_per);
                // create log file
                $server_memory_per_rra = new ServerMemoryUsageRRA($node_uuid,$etva_server->getUuid());
                //send/update file information
                $return = $server_memory_per_rra->update($server_memory_per);



                $server_memory_sort = array($server_memory_data->mem_m,
                                            $server_memory_data->mem_v);

                $server_memory = new ServerMemoryRRA($node_uuid,$etva_server->getUuid());
                $server_memory->update($server_memory_sort);



                }// end virtualmachine stuff

            }

      //  $this->updateVirtAgentLogs_($node_uuid,$data);

        $all_params = array('request'=>array('uuid'=>$etva_node->getUuid(),
                                            'data'=>$data,
                                            'method'=>'updateVirtAgentLogs'
                                    ),
                            'response'=>$return);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));


        return $return;

    }

    /*
     * Initializes server with services...
     */
    function initAgentServices($agent_name,$agent_ip,$agent_port,$server_mac,$services)
    {
        // log function called
        $request_array = array('request'=>
                            array(
                                'name'=>$agent_name,
                                'ip'=>$agent_ip,
                                'port'=>$agent_port,
                                'macaddr'=>$server_mac,
                                'method'=>'initAgentServices',
                                'services'=>$services));


        $this->request->setParameter('name', $agent_name);
        $this->request->setParameter('ip', $agent_ip);
        $this->request->setParameter('port', $agent_port);
        $this->request->setParameter('macaddr', $server_mac);
        $this->request->setParameter('services',$services);

        $action = $this->getAction('service','soapInit');
        $result = $action->executeSoapInit($this->request);        

        $response_array = array('response'=>$result);

        $all_params = array_merge($request_array,$response_array);

        // log function called
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));


        return $result;

    }

}
