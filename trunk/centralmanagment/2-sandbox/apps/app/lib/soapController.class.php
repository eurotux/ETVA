<?php
require_once 'SOAP/Client.php';

class soapController extends sfController {


    // public $request;

    public function __construct(){
      /**
       * Since we're bypassing Symfony's action dispatcher, we have to initialize manually.
       */
        $this->context = sfContext::getInstance();
        $this->request = $this->context->getRequest();


        $this->__dispatch_map['initializeVirtAgent'] = array(
                                                     'in'    => array('agent_name'=>'string',
                                                                        'agent_mem'=>'integer',
                                                                        'agent_memfree'=>'integer',
                                                                        'agent_cpu'=>'integer',
                                                                        'agent_ip'=>'string',
                                                                        'agent_port'=>'integer',
                                                                        'agent_uid'=>'string',
                                                                        'agent_state'=>'integer'),
                                                     'out'   => array('return'=>"{urn:soapController}Tuple")
        );

        $this->__typedef['Tuple'] = array('success'=>'boolean','insert_id'=>'integer');

        $this->__dispatch_map['updateVirtAgent'] = array(
                                                        'in'    => array('uid'=>'string',
                                                                        'field'=>'string',
                                                                        'value'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        $this->__dispatch_map['updateVirtAgentVlans'] = array(
                                                        'in'    => array('uid'=>'string',
                                                                        'vlans'=>array('string')),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        $this->__dispatch_map['updateVirtAgentDevices'] = array(
                                                        'in'    => array('uid'=>'string',
                                                                        'devs'=>array('string')),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );



        $this->__dispatch_map['updateVirtAgentPvs'] = array(
                                                        'in'    => array('uid'=>'string',
                                                                        'pvs'=>array('string')),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );

        $this->__dispatch_map['updateVirtAgentLvs'] = array(
                                                        'in'    => array('uid'=>'string',
                                                                        'lvs'=>array('string')),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );


        $this->__dispatch_map['updateVirtAgentVgs'] = array(
                                                        'in'    => array('uid'=>'string',
                                                                        'vgs'=>array('string')),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );




        $this->__dispatch_map['updateVirtAgentServers'] = array(
                                                        'in'    => array('uid'=>'string',
                                                                        'vms'=>array('string')),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );


        $this->__dispatch_map['updateVirtAgentLogs'] = array(
                                                        'in'    => array('uid'=>'string',
                                                                        'info'=>array('string')),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );


        $this->__dispatch_map['initAgentServices'] = array(
                                                        'in'    => array(
                                                                         'name'=>'string',
                                                                         'ip'=>'string',
                                                                         'port'=>'integer',
                                                                        'macaddr'=>'string',
                                                                        'services'=>array('string')),
                                                        'out'   => array('return'=>"{urn:soapController}ArraySuccess")
        );



        $this->__typedef['ArraySuccess'] = array('success'=>'boolean');



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
    * receives initialization data from node virt agent and store it in db
    */
    function initializeVirtAgent($node_name, $node_mem, $node_memfree, $node_cpu, $node_ip, $node_port, $node_uid, $node_state){

        //     $this->soapAuth($user, $password); //I call this at the start of each function call in the soap controller (You can choose not to do it)

        $array = array(
                'name'=>$node_name,
                'memtotal'=>$node_mem,
                'memfree'=>$node_memfree,
                'cputotal'=>$node_cpu,
                'ip'=>$node_ip,
                'port'=>$node_port,
                'uid'=>$node_uid,
                'state'=>$node_state);


        $this->request->setParameter('etva_node', $array);

        $action = $this->getAction('node','soapCreate');
        $result = $action->executeSoapCreate($this->request);

        return $result;
    }

    function updateVirtAgent($node_uid, $node_field, $node_value)
    {

        $array = array(
                'field'=>$node_field,
                'value'=>$node_value,
        );
        $this->request->setParameter('uid', $node_uid);
        $this->request->setParameter('field',$node_field);
        $this->request->setParameter('value',$node_value);

        $action = $this->getAction('node','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        return $result;
    }

    function updateVirtAgentServers($node_uid, $vms)
    {



        $this->request->setParameter('uid', $node_uid);
        $this->request->setParameter('vms',$vms);

        $action = $this->getAction('server','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);
        return $result;

    }


    function updateVirtAgentVlans($node_uid, $vlans)
    {

        $this->request->setParameter('uid', $node_uid);
        $this->request->setParameter('vlans',$vlans);

        $action = $this->getAction('vlan','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        return $result;
    }


    function updateVirtAgentDevices($node_uid, $devs)
    {


        $this->request->setParameter('uid', $node_uid);
        $this->request->setParameter('devs',$devs);

        $action = $this->getAction('physicalvol','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        return $result;
    }

    function updateVirtAgentPvs($node_uid, $lvs)
    {


        //    $this->request->setParameter('uid', $node_uid);
        //    $this->request->setParameter('lvs',$lvs);
        //
        //    $action = $this->getAction('logicalvol','soapUpdate');
        //    $result = $action->executeSoapUpdate($this->request);
        $result = array('success'=>true);
        return $result;
    }




    function updateVirtAgentLvs($node_uid, $lvs)
    {


        $this->request->setParameter('uid', $node_uid);
        $this->request->setParameter('lvs',$lvs);

        $action = $this->getAction('logicalvol','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        return $result;
    }


    function updateVirtAgentVgs($node_uid, $vgs)
    {


        $this->request->setParameter('uid', $node_uid);
        $this->request->setParameter('vgs',$vgs);

        $action = $this->getAction('volgroup','soapUpdate');
        $result = $action->executeSoapUpdate($this->request);

        return $result;
    }


    /**
    *
    * Convert an object to an array
    *
    * @param    object  $object The object to convert
    * @reeturn      array
    *
    */
    function objectToArray( $object )
    {
        if( !is_object( $object ) && !is_array( $object ) )
        {
            return $object;
        }
        if( is_object( $object ) )
        {
            $object = get_object_vars( $object );
        }
        return array_map(array($this,'objectToArray'), $object );
    }



    function updateVirtAgentLogs($node_uid,$data)
    {

        $c = new Criteria();
        $c->add(EtvaNodePeer::UID ,$node_uid);

        $etva_node = EtvaNodePeer::doSelectOne($c);
        $node_name = $etva_node->getName();


        $cpu_load = $data->load;
        $cpu_load_sort = array($cpu_load->onemin,
            $cpu_load->fivemin,
            $cpu_load->fifteenmin
        );

        $cpu_load = new NodeLoadRRA($node_name);
        $cpu_load->update($cpu_load_sort);

        $virtServers = $data->virtualmachines;

        if(!$virtServers) return array('success'=>true);

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
                    $server_network_rra = new ServerNetworkRRA($node_name,$etva_server->getName(),$mac_strip);
                    //send/update file information
                    $return = $server_network_rra->update($intf_sort);

                    $logger = new virtAgentLogger(sfContext::getInstance()->getEventDispatcher(),array('file' => sfConfig::get("sf_log_dir").'/virtAgent/lixo.xml'));
                    // $logger->log("tx ".$tx." rx ".$rx);
                }


            }// end server networks


            /*
             * disk stuff
             */
            if(isset($server_data->disk)){
                $server_disk_data = $server_data->disk;

                foreach($server_disk_data as $diskname=>$diskdata){


                    $size = $diskdata->size;
                    $freesize = $diskdata->freesize;
                    $disk_space = array($size,$freesize);

                    $read_t = $diskdata->r_spent;
                    $write_t = $diskdata->w_spent;
                    $disk_sort = array($read_t,$write_t);

                    // create log file
                    $server_disk_rw_log = new ServerDisk_rwspentRRA($node_name,$etva_server->getName(),$diskname);
                    //send/update file information
                    $return = $server_disk_rw_log->update($disk_sort);

                    $server_disk_spaceRRA = new ServerDiskSpaceRRA($node_name,$etva_server->getName(),$diskname);
                    $return = $server_disk_spaceRRA->update($disk_space);

                    $logger = new virtAgentLogger(sfContext::getInstance()->getEventDispatcher(),array('file' => sfConfig::get("sf_log_dir").'/virtAgent/lixo.xml'));
                    // $logger->log("readtime ".$read_t." writetime ".$write_t);



                }// end server disk
            }

            // store cpu utilization
            $server_cpu_data = $server_data->cpu;
            $server_cpu_per = array($server_cpu_data->cpu_per);

    //        $logger = new virtAgentLogger(sfContext::getInstance()->getEventDispatcher(),array('file' => sfConfig::get("sf_log_dir").'/virtAgent/lixo.xml'));
      //      $logger->log("cpu ".$server_cpu_data->cpu_per);

            // create log file
            $server_cpu_per_rra = new ServerCpuUsageRRA($node_name,$etva_server->getName());
            //send/update file information
            $return = $server_cpu_per_rra->update($server_cpu_per);


            /*
             * store memory info
             */

            // store memory utilization
            $server_memory_data = $server_data->memory;

            $server_memory_per = array($server_memory_data->mem_per);
            // create log file
            $server_memory_per_rra = new ServerMemoryUsageRRA($node_name,$etva_server->getName());
            //send/update file information
            $return = $server_memory_per_rra->update($server_memory_per);



//            $server_memory_buffers = array($server_memory_data->mem_v);

//            $server_memory_swap = array($server_memory_data->mem_m);
            
            $server_memory_sort = array($server_memory_data->mem_m,
                                        $server_memory_data->mem_v);

            $server_memory = new ServerMemoryRRA($node_name,$etva_server->getName());
            $server_memory->update($server_memory_sort);

 //           $server_memory_buffers_rra = new ServerMemory_buffersRRA($node_name,$etva_server->getName());
 //           $return = $server_memory_buffers_rra->update($server_memory_buffers);

 //           $server_memory_swap_rra = new ServerMemory_swapRRA($node_name,$etva_server->getName());
 //           $return = $server_memory_swap_rra->update($server_memory_swap);


            }// end virtualmachine stuff


            // $logger = new virtAgentLogger(sfContext::getInstance()->getEventDispatcher(),array('file' => sfConfig::get("sf_log_dir").'/virtAgent/lixo.xml'));
            // $logger->log("swap ".$server_memory_data->mem_m." buffers ".$server_memory_data->mem_v);


            // $return = $cpu_load_log->update($cpu_load_sort);

        }

      //  $this->updateVirtAgentLogs_($node_uid,$data);



        return $return;

    }

    function initAgentServices($agent_name,$agent_ip,$agent_port,$server_mac,$services)
    {
        
        $this->request->setParameter('name', $agent_name);
        $this->request->setParameter('ip', $agent_ip);
        $this->request->setParameter('port', $agent_port);
        $this->request->setParameter('macaddr', $server_mac);
        $this->request->setParameter('services',$services);

        $action = $this->getAction('service','soapInit');
        $result = $action->executeSoapInit($this->request);

        return $result;
        
    }


    function updateVirtAgentLogs_($node_uid,$info)
    {
        require_once 'XML/Serializer.php';

        $c = new Criteria();
        $c->add(EtvaNodePeer::UID ,$node_uid);


        $etva_node = EtvaNodePeer::doSelectOne($c);
        $node_name = $etva_node->getName();
        $options = array(
          XML_SERIALIZER_OPTION_INDENT        => '    ',
          XML_SERIALIZER_OPTION_RETURN_RESULT => true,
          XML_SERIALIZER_OPTION_ROOT_NAME => 'da'
        );


       // $time = $info->time;
        //$view_time = date("Ymd",$time);


        $serializer = new XML_Serializer($options);


        //$array = $this->objectToArray( $info );
        $result = $serializer->serialize($info);

$logger = new virtAgentLogger(sfContext::getInstance()->getEventDispatcher(),array('file' => sfConfig::get("sf_log_dir").'/virtAgent/datasent.xml'));
                 $logger->log($result);
                 return '';
        /*
         * create/append files
         */
        $virtServers = $info->virtualmachines;
        //return $virtServers;

        $store_logs_of_virtServers = array('network','cpu_per','disk','mem_per');

        foreach($virtServers as $server_name => $data){

            foreach($store_logs_of_virtServers as $tag){

                $options = array(
                                 "indent"          => "    ",
                                 "rootName"        => $tag,
                                 "returnResult"    => true
                );

                $serializer = new XML_Serializer($options);
                $result = $serializer->serialize($data->$tag);

                $logger = new virtAgentLogger(sfContext::getInstance()->getEventDispatcher(),array('file' => sfConfig::get("sf_log_dir").'/virtAgent/'.$node_name.'/'.$server_name.'/'.$tag.'_'.$view_time.'.xml'));
                // $logger->log($result);

            }

        }

        $store_logs_of_virtAgent = array('network','cpu','disk','memory','load');

        foreach($store_logs_of_virtAgent as $tag){

            $options = array(
                                 "indent"          => "    ",
                                 "rootName"        => $tag,
                                 "returnResult"    => true
            );

            $serializer = new XML_Serializer($options);
            $result = $serializer->serialize($info->$tag);

            $logger = new virtAgentLogger(sfContext::getInstance()->getEventDispatcher(),array('file' => sfConfig::get("sf_log_dir").'/virtAgent/'.$node_name.'/'.$tag.'_'.$view_time.'.xml'));
            //  $logger->log($result);

        }


        $return = array('success'=>true);


        return $return;


    }



}
