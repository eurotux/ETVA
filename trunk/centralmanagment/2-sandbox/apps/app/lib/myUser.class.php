<?php

class myUser extends sfGuardSecurityUser
{
    public function getLastLogin()
    {
        return $this->getGuardUser()->getLastLogin();
    }

    public function getId()
    {
        return $this->getGuardUser()->getId();
    }

    public function initVncToken()
    {

        $user_id = $this->getGuardUser()->getId();
        $user_name = $this->getUsername();

        $vncToken = EtvaVncTokenPeer::retrieveByPK($user_name);
        if(!$vncToken) $vncToken = new EtvaVncToken();

        // generate new data

        $tokens = self::generatePairToken();

        $vncToken->setUserId($user_id);
        $vncToken->setUsername($user_name);
        $vncToken->setToken($tokens[0]);
        $vncToken->setEnctoken($tokens[1]);
        $vncToken->save();

    }


    protected function generatePairToken($len = 20)
    {
        $string = '';
        $pool   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for ($i = 1; $i <= $len; $i++)
        {
            $string .= substr($pool, rand(0, 61), 1);
        }
        $token = $string;
        $enctoken = '{SHA}'.base64_encode (sha1($string,true));

        return array($token,$enctoken);
    }


    /*
     * function to check/set if is first time request
     */
    public function isFirstRequest($boolean = null)
    {
        if (is_null($boolean))
        {
            return $this->getAttribute('first_request', true);
        }

        $this->setAttribute('first_request', $boolean);
    }

    public function hasDatacenterCredential($credential, $useAnd = true)
    {
        if ($this->isSuperAdmin()){
            return true;
        }

        $p_permtype = $credential[0];
        $p_user_id = $this->getId();
        //$p_cluster_id = $credential[1];

        if(is_array ( $credential[1] )){
            $p_type_credential = (array)$credential[1];
            if( $p_type_credential['node'] ){
                error_log("MYUSER:[INFO]Node id: ".$p_type_credential['node']);
                $dc_c = new Criteria();
                $dc_c->add(EtvaNodePeer::ID, $p_type_credential['node']);
                $node = EtvaNodePeer::doSelectOne($dc_c);
                $p_cluster_id = $node->getClusterId();
            }elseif($p_type_credential['server']){
                error_log("MYUSER:[INFO]Server id: ".$p_type_credential['server']);
                $dc_c = new Criteria();
                $dc_c->add(EtvaServerPeer::ID, (int)$p_type_credential['server'], Criteria::EQUAL);
                $server = EtvaServerPeer::doSelectOne($dc_c);
                $p_cluster_id = $server->getClusterId();
            }elseif($p_type_credential['cluster']){
                error_log("MYUSER:[INFO]Cluster id: ".$p_type_credential['cluster']);
                $p_cluster_id = (int)$p_type_credential['cluster'];
            }else{
                error_log('MYUSER:[ERROR] hasDatacenterCredential invalid parameters');
                return false;
            }
        }else{
            $p_cluster_id = $credential[1];
        }

        // Check if user has permission
        $c = new Criteria();
        $c->add(EtvaPermissionUserPeer::USER_ID, $p_user_id, Criteria::EQUAL);
        $c->addJoin(EtvaPermissionUserPeer::ETVAPERM_ID, EtvaPermissionPeer::ID);
        $c->add(EtvaPermissionPeer::PERM_TYPE, $p_permtype, Criteria::EQUAL);
        $c->addJoin(EtvaPermissionPeer::ID, EtvaPermissionClusterPeer::ETVAPERM_ID);
        $c->add(EtvaPermissionClusterPeer::CLUSTER_ID, $p_cluster_id);
        //error_log($c->toString());

        $permission = EtvaPermissionUserPeer::doSelect($c);

        if ($permission){
            return true;
        }

        // Check if user groups have permission
        $grps = $this->getGroups();

        foreach ($grps as $value){
            foreach ($value->getEtvaPermissionGroups() as $relObj) {
                $perm = $relObj->getEtvaPermission();

                // validates the permission type
                if ($perm->getPermType() == $p_permtype){
                    
                    // check if user has permission on the cluster
                    foreach ($perm->getEtvaPermissionClusters() as $clusters){
                        if ($clusters->getClusterId() == $p_cluster_id){
                            error_log("Permission:  ".$clusters->getClusterId());
                            return true;
                        }
                    }
                }
            }
        }

        // Permission not found
        return false;
    }
    
    public function hasServerCredential($credential, $useAnd = true)
    {
        if ($this->isSuperAdmin()){
            return true;
        }

        $p_permtype = $credential[0];
        $p_user_id = $this->getId();
        $p_server_id = $credential[1];

        error_log("has server credential ".$p_permtype." ; user: ".$p_user_id." ; server: ".$p_server_id);

        // Check if user has permission
        $c = new Criteria();
        
        $c->add(EtvaPermissionUserPeer::USER_ID, $p_user_id, Criteria::EQUAL);
        $c->addJoin(EtvaPermissionUserPeer::ETVAPERM_ID, EtvaPermissionPeer::ID);
        $c->add(EtvaPermissionPeer::PERM_TYPE, $p_permtype, Criteria::EQUAL);
        $c->addJoin(EtvaPermissionPeer::ID, EtvaPermissionServerPeer::ETVAPERM_ID);
        $c->add(EtvaPermissionServerPeer::SERVER_ID, $p_server_id);
//        error_log($c->toString());
        $permission = EtvaPermissionUserPeer::doSelect($c);

        if ($permission){
            return true;
        }

        // Check if user groups have permission
        $grps = $this->getGroups();

        foreach ($grps as $value){
            foreach ($value->getEtvaPermissionGroups() as $relObj) {
                $perm = $relObj->getEtvaPermission();

                // validates the permition type
                if ($perm->getPermType() == $p_permtype){

                    // check if user has permission on the server
                    foreach ($perm->getEtvaPermissionServers() as $servers){
                        if ($servers->getServerId() == $p_server_id){
                            return true;
                        }
                    }
                }
            }
        }

        // Permission not found
        return false;
    }

    public function hasNodeC($credential, $useAnd = true)
    {
        if ($this->isSuperAdmin()){
            return true;
        }

        $p_permtype = $credential[0];
        $p_user_id = $this->getId();
        $p_node_id = $credential[1];

        // Check if user has permission
        $c = new Criteria();
        $c->add(EtvaPermissionUserPeer::USER_ID, $p_user_id, Criteria::EQUAL);
        $c->add(EtvaPermissionPeer::PERM_TYPE, $p_permtype, Criteria::EQUAL);
        $c->addJoin(EtvaPermissionPeer::ID, EtvaPermissionNodePeer::ETVAPERM_ID);
        $c->add(EtvaPermissionNodePeer::NODE_ID, $p_node_id);
        $permission = EtvaPermissionUserPeer::doSelect($c);

        if ($permission){
            return true;
        }

        // Check if user groups have permission
//        $grps = $this->getGroups();
//
//        foreach ($grps as $value){
//            foreach ($value->getEtvaPermissionGroups() as $relObj) {
//                $perm = $relObj->getEtvaPermission();
//
//                // validates the permition type
//                if ($perm->getPermType() == $p_permtype){
//
//                    // check if user has permission on the node
//                    foreach ($perm->getEtvaPermissionNodes() as $nodes){
//                        if ($nodes->getNodeId() == $p_node_id){
//                            return true;
//                        }
//                    }
//                }
//            }
//        }

        // Permission not found
        return false;
    }

    //TODO: validar grupos
    public function hasCredential($credential, $useAnd = true)
    {
        if ($this->isSuperAdmin()){
            return true;
        }

        if(sizeof($credential) == 0)
            return true;        // Discuss with cmar

        //perm_type, dcenter, node, vm
        $p_permtype = $credential[0];
        $p_user_id = $this->getId();
        $p_cluster_id = $credential[1];
        $p_server_id = $credential[2];
        

        $c = new Criteria();
        $c->add(EtvaPermissionUserPeer::USER_ID, $p_user_id, Criteria::EQUAL);
        $c->addJoin(EtvaPermissionUserPeer::ETVAPERM_ID, EtvaPermissionPeer::ID);
        $c->add(EtvaPermissionPeer::PERM_TYPE, $p_permtype, Criteria::EQUAL);
        $c->addJoin(EtvaPermissionPeer::ID, EtvaPermissionClusterPeer::ETVAPERM_ID);
        $c->add(EtvaPermissionClusterPeer::CLUSTER_ID, $p_cluster_id);
        $c->addJoin(EtvaPermissionPeer::ID, EtvaPermissionServerPeer::ETVAPERM_ID);
        $c->add(EtvaPermissionServerPeer::SERVER_ID, $p_server_id);

        $permission = EtvaPermissionUserPeer::doSelect($c);

        return ($permission) ? true : false;
    }

    /**
     * Returns true if user is authenticated.
     *
     * @return boolean
    */ 
    public function isAuthenticated()
    {
      if (!$this->authenticated)
      {      
        if ($cookie = sfContext::getInstance()->getRequest()->getCookie(sfConfig::get('app_sf_guard_plugin_remember_cookie_name', 'sfRemember')))
        {
          $c = new Criteria();
          $c->add(sfGuardRememberKeyPeer::REMEMBER_KEY, $cookie);
          $rk = sfGuardRememberKeyPeer::doSelectOne($c);
          if ($rk && $rk->getSfGuardUser())
          {
            $this->signIn($rk->getSfGuardUser());
            $this->initVncToken();
          }
        }
      }
      
      return $this->authenticated;
    }
}
