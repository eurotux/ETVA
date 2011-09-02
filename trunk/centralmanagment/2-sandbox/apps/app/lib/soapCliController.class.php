<?php

class soapCliController extends sfController {


    // public $request;

    public function __construct(){
      /**
       * Since we're bypassing Symfony's action dispatcher, we have to initialize manually.
       */
        $this->context = sfContext::getInstance();
        $this->request = $this->context->getRequest();        
        

        $this->__typedef['Tuple'] = array('success'=>'boolean','insert_id'=>'integer');

               

/*
 * PHYSICAL VOLUME
 *
 */
        $this->__dispatch_map['cli_pvListAllocatable'] = array(
                                                        'in'    => array('host'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_pvList'] = array(
                                                        'in'    => array('host'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_pvcreate'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'dev'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_pvremove'] = array(
                                                        'in'    => array('host'=>'string',
                                                                         'dev'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

/*
 *
 * VOLUME GROUP
 *
 */

         $this->__dispatch_map['cli_vgList'] = array(
                                                        'in'    => array('host'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_vgupdate'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'vgname'=>'string',
                                                                        'pvs'=>'array'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );


        $this->__dispatch_map['cli_vgreduce'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'vgname'=>'string',
                                                                        'pvs'=>'array'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );


        $this->__dispatch_map['cli_vgremove'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'vgname'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );
        


/*
 * LOGICAL VOLUME
 *
 */
        $this->__dispatch_map['cli_lvcreate'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'lv'=>'string',
                                                                        'vg'=>'string',
                                                                        'size'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}ArraySuccess")
        );

        $this->__dispatch_map['cli_lvList'] = array(
                                                        'in'    => array('host'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_lvresize'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'lv'=>'string',                                                                      
                                                                        'size'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}ArraySuccess")
        );

        $this->__dispatch_map['cli_lvremove'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'lv'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}ArraySuccess")
        );


        /*
         *
         * VLAN
         *
         */

        $this->__dispatch_map['cli_vlancreate'] = array(
                                                        'in'    => array('netname'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}ArraySuccess")
        );

        $this->__dispatch_map['cli_vlanList'] = array(
                                                        'in'    => array(),
                                                        'out'   => array('return'=>"{urn:soapCliController}ArraySuccess")
        );

        $this->__dispatch_map['cli_vlanremove'] = array(
                                                        'in'    => array('netname'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}ArraySuccess")
        );


        /*
         *
         * NETWORK
         *
         */

        $this->__dispatch_map['cli_networkreplace'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'server'=>'string',
                                                                        'netsintfs'=>'array'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_networkdetach'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'server'=>'string',
                                                                        'mac'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );


        /*
         * NODES
         */

        $this->__dispatch_map['cli_nodeList'] = array(
                                                        'in'    => array(),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );


        /*
         *
         * SERVER
         *
         */

        $this->__dispatch_map['cli_servercreate'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'server'=>'array',
                                                                        'username'=>'string',
                                                                        'password'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_serverremove'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'server'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_serverstart'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'server'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        $this->__dispatch_map['cli_serverstop'] = array(
                                                        'in'    => array('host'=>'string',
                                                                        'server'=>'string'),
                                                        'out'   => array('return'=>"{urn:soapCliController}Tuple")
        );

        

    }

  

   /*
    * PHYSICAL VOLUME
    */
    function cli_pvcreate($host, $dev)
    {
        
        if(!$etva_node = EtvaNodePeer::retrieveByIp($host))
        {            
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);
        }
        
        $nid = $etva_node->getId();
        $this->request->setParameter('nid', $nid);
        $this->request->setParameter('dev', $dev);
        
        $action = $this->getAction('physicalvol','jsonInit');
        $result = $action->executeJsonInit($this->request);

        return $result;
    }


    function cli_pvremove($host, $dev)
    {     
        if(!$etva_node = EtvaNodePeer::retrieveByIp($host))
        {
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);
        }

        $nid = $etva_node->getId();
        $this->request->setParameter('nid', $nid);
        $this->request->setParameter('dev', $dev);
        
        $action = $this->getAction('physicalvol','jsonUninit');
        $result = $action->executeJsonUninit($this->request);

        return $result;
    }


    function cli_pvList($host)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){

            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);
        }

        $nid = $etva_node->getId();
        $this->request->setParameter('nid', $nid);

        $action = $this->getAction('physicalvol','jsonList');
        $result = $action->executeJsonList($this->request);


        return $result;
    }

    function cli_pvListAllocatable($host)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){

            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);
        }

        $nid = $etva_node->getId();
        $this->request->setParameter('nid', $nid);

        $action = $this->getAction('physicalvol','jsonListAllocatable');
        $result = $action->executeJsonListAllocatable($this->request);


        return $result;
    }


 /*
  * END PHYSICAL VOLUME
  */


 /*
  * VOLUME GROUP
  *
  */

    function cli_vgupdate($host, $vg, $pvs)
    {           

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }


        $this->request->setParameter('nid',$etva_node->getId());
        $this->request->setParameter('vg',$vg);
        $this->request->setParameter('pvs', json_encode($pvs));

        $action = $this->getAction('volgroup','jsonUpdate');

        $result = $action->executeJsonUpdate($this->request);

        return $result;
    }



    function cli_vgreduce($host, $vg, $pvs)
    {        

        // get DB info by pv name
        if(!$etva_node = EtvaNodePeer::retrieveByIp($host))
        {
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);


        }

        $nid = $etva_node->getId();

        $this->request->setParameter('nid', $nid);
        $this->request->setParameter('vg',$vg);
        $this->request->setParameter('pvs', json_encode($pvs));

        $action = $this->getAction('volgroup','jsonReduce');

        $result = $action->executeJsonReduce($this->request);

        return $result;
    }


    function cli_vgremove($host, $vg)
    {        

        // get DB info by pv name
        if(!$etva_node = EtvaNodePeer::retrieveByIp($host))
        {
            // physical volume with that name not exist
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);


        }
        $nid = $etva_node->getId();
        
        $this->request->setParameter('nid', $nid);
        $this->request->setParameter('vg',$vg);        

        $action = $this->getAction('volgroup','jsonRemove');

        $result = $action->executeJsonRemove($this->request);

        return $result;
    }



    function cli_vgList($host)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);
        }

        $nid = $etva_node->getId();
        $this->request->setParameter('nid', $nid);       

        $action = $this->getAction('volgroup','jsonList');
        $result = $action->executeJsonList($this->request);



        return $result;
    }



 /*
  * LOGICAL VOLUME
  *
  */

    function cli_lvcreate($host, $lv, $vg, $size)
    {                

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){

            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }

        $nid = $etva_node->getId();
        $this->request->setParameter('nid',$nid);
        $this->request->setParameter('lv',$lv);
        $this->request->setParameter('vg',$vg);
        $this->request->setParameter('size', $size);

        $action = $this->getAction('logicalvol','jsonCreate');

        $result = $action->executeJsonCreate($this->request);

        return $result;
    }

    function cli_lvList($host)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);
        }

        $nid = $etva_node->getId();
        $this->request->setParameter('nid', $nid);

        $action = $this->getAction('logicalvol','jsonList');
        $result = $action->executeJsonList($this->request);


        return $result;
    }


    function cli_lvresize($host, $lv, $size)
    {       

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){

            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }

        $nid = $etva_node->getId();
        $this->request->setParameter('nid',$nid);
        $this->request->setParameter('lv',$lv);        
        $this->request->setParameter('size', $size);

        $action = $this->getAction('logicalvol','jsonResize');

        $result = $action->executeJsonResize($this->request);

        return $result;
    }


    function cli_lvremove($host, $lv)
    {        

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){

            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }

        $nid = $etva_node->getId();
        $this->request->setParameter('nid',$nid);
        $this->request->setParameter('lv',$lv);                

        $action = $this->getAction('logicalvol','jsonRemove');

        $result = $action->executeJsonRemove($this->request);

        return $result;
    }


    /*
     *
     * VLAN
     *
     */

    function cli_vlancreate($netname)
    {        
     
        $this->request->setParameter('name',$netname);

        $action = $this->getAction('vlan','jsonCreate');

        $result = $action->executeJsonCreate($this->request);

        return $result;
    }

    function cli_vlanList()
    {                

        $action = $this->getAction('vlan','jsonList');
        $result = $action->executeJsonList($this->request);

        return $result;
    }

    function cli_vlanremove($netname)
    {

        $this->request->setParameter('name',$netname);

        $action = $this->getAction('vlan','jsonRemove');

        $result = $action->executeJsonRemove($this->request);

        return $result;
    }


    /*
     * NETWORK
     */


    function cli_networkreplace($host, $server, $nets_intfs)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }

        if(!$etva_server = $etva_node->retrieveServerByName($server)){
            $error = array('success'=>false,'error'=>$host.' : Server name not found');
            return json_encode($error);
        }
        
        $this->request->setParameter('sid',$etva_server->getId());
        $this->request->setParameter('networks', json_encode($nets_intfs));

        $action = $this->getAction('network','jsonReplace');

        $result = $action->executeJsonReplace($this->request);

        return $result;
    }

    function cli_networkdetach($host,$server,$mac)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }

        if(!$etva_server = $etva_node->retrieveServerByName($server)){
            $error = array('success'=>false,'error'=>$host.' : Server name not found');
            return json_encode($error);
        }


        $this->request->setParameter('sid',$etva_server->getId());
        $this->request->setParameter('macaddr', $mac);

        $action = $this->getAction('network','jsonRemove');

        $result = $action->executeJsonRemove($this->request);

        return $result;


    }

    /*
     * NODE
     *
     */
    

    function cli_nodeList()
    {        
      
        $action = $this->getAction('node','jsonNodeList');

        $result = $action->executeJsonNodeList($this->request);

        return $result;
  
    }

    

    /*
     *
     * SERVER
     *
     */

    function cli_servercreate($host,$server,$username,$password)
    {
        
        $this->soapAuth($username, $password);

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }


        $this->request->setParameter('nid',$etva_node->getId());
        $this->request->setParameter('server', json_encode($server));

        $action = $this->getAction('server','jsonCreate');

        $result = $action->executeJsonCreate($this->request);

        return $result;


    }

    function cli_serverremove($host,$server)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }


        $this->request->setParameter('nid',$etva_node->getId());
        $this->request->setParameter('server', $server);

        $action = $this->getAction('server','jsonRemove');

        $result = $action->executeJsonRemove($this->request);

        return $result;


    }

    function cli_serverstart($host,$server)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }


        $this->request->setParameter('nid',$etva_node->getId());
        $this->request->setParameter('server', $server);

        $action = $this->getAction('server','jsonStart');

        $result = $action->executeJsonStart($this->request);

        return $result;


    }

    function cli_serverstop($host,$server)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByIp($host)){
            $error = array('success'=>false,'error'=>$host.' : Virt agent not found');
            return json_encode($error);

        }


        $this->request->setParameter('nid',$etva_node->getId());
        $this->request->setParameter('server', $server);

        $action = $this->getAction('server','jsonStop');

        $result = $action->executeJsonStop($this->request);

        return $result;


    }


    // Authentication function

    function soapAuth($username,$password)
    {


       try {

            $c = new Criteria();
            $c->add(sfGuardUserPeer::USERNAME ,$username);


            $check = sfGuardUserPeer::doSelectOne($c);

            if($check){
                $check_pass = $check->getPassword();
                $check_alg = $check->getAlgorithm();

                if($check_pass == call_user_func_array($check_alg, array($check->getSalt().$password))){

                    $user = $this->context->getUser();
                    $user->addCredentials($check->getAllPermissionNames());                                     
                    $user->setAuthenticated(true);
                 
                    $this->context->set('user',$check);
                 
                }else throw new Exception('Soap Authentication failed');


            }
            else
                throw new Exception('Soap Authentication failed');

        }catch (Exception $e){
            //throw new Expection($e->getMessage());
            trigger_error($e->getMessage());
             //throw new SOAP_Fault('12345');
        }
    }




}
?>