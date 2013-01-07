<?php

class EtvaServer extends BaseEtvaServer
{
    const NAME_MAP = 'name';
    const MEMORY_MAP = 'memory';
    const VCPU_MAP = 'vcpu';
    const VNC_PORT_MAP = 'vnc_port';
    const VNC_KEYMAP_MAP = 'vnc_keymap';
    const VM_STATE_MAP = 'state';
    const DISKS_MAP = 'Disks';
    const NETWORKS_MAP = 'Network';
    const FEATURES_MAP = 'features';

    const RUNNING = 'running';
    const STATE_RUNNING = 'running';
    const STATE_STOP = 'stop';
    const STATE_OK = 1;
    const STATE_NOK = 0;

    /*
     * update some object data from VA response
     */
    public function initData($arr)
	{
        if(array_key_exists(self::NAME_MAP, $arr)) $this->setName($arr[self::NAME_MAP]);

        if(array_key_exists(self::MEMORY_MAP, $arr)){
            $memory = Etva::Byte_to_MBconvert($arr[self::MEMORY_MAP]);
            $this->setMem($memory);
        }

        if(array_key_exists(self::VCPU_MAP, $arr)) $this->setCpus($arr[self::VCPU_MAP]);
        if(array_key_exists(self::VNC_PORT_MAP, $arr)) $this->setVncPort($arr[self::VNC_PORT_MAP]);
        if(array_key_exists(self::VNC_KEYMAP_MAP, $arr)) $this->setVncKeymap($arr[self::VNC_KEYMAP_MAP]);
        if(array_key_exists(self::VM_STATE_MAP, $arr)) $this->setVmState($arr[self::VM_STATE_MAP]);
        if(array_key_exists(self::DISKS_MAP, $arr)) $this->setDisks($arr[self::DISKS_MAP]);
        if(array_key_exists(self::NETWORKS_MAP, $arr)) $this->setNetworks($arr[self::NETWORKS_MAP]);
        if(array_key_exists(self::FEATURES_MAP, $arr)) $this->setFeatures(json_encode($arr[self::FEATURES_MAP]));
        
	}


    public function setVncPort($port = null)
    {

        if($port) $vncPort = EtvaVncPortPeer::retrieveByPK($port);     

        if(!$vncPort) $vncPort = EtvaVncPortPeer::getByServer($this->getId());

        if(!$vncPort) $vncPort = EtvaVncPortPeer::getUnusedPort();                                
        
        $vncPort->setServerId($this->getId());
        $vncPort->setEtvaServer($this);
        $vncPort->setInuse(1);
        
        return $vncPort;

    }
    
    public function getVncPort()
    {
        $vncPort = null;
        if($this->getId()) $vncPort = EtvaVncPortPeer::getByServer($this->getId());

        if(!$vncPort) $vncPort = EtvaVncPortPeer::getUnusedPort();

        return $vncPort->getId();        
    }




    /*
     * set cpus info. vcps and cpuset
     */
    private function setCpus($vpcus)
    {
        $cpu_set = array();
        for($i=0;$i<$vpcus;$i++){
            $cpu_set[$i] = $i;
        }
        $cpu_set_string = implode(',',$cpu_set);
        $this->setCpuset($cpu_set_string);
        $this->setVcpu($vpcus);
        

    }

    /*
     * used to process soap agent disks data
     */
    private function setDisks($data)
    {
        $sls = $this->getEtvaServerLogicals();
        foreach($sls as $sl){
            $sl_lv = $sl->getEtvaLogicalvolume();

            foreach ($data as $disktag=>$disk_info){
                $disk = (array) $disk_info;

                if($disk[EtvaLogicalvolume::PATH_MAP]==$sl_lv->getLvdevice()){
                    $sl->initData($disk);          
                    break;
                }
            }
        }        
    }

     /*
     * used to process soap agent networks data
     */
    private function setNetworks($data)
    {
        $networks = $this->getEtvaNetworks();
        foreach($networks as $network){            

            foreach ($data as $intfport=>$intfdata){
                $intf = (array) $intfdata;

                if($intf[EtvaNetwork::MACADDR_MAP]==$network->getMac()){
                    $network->initData($intf);
                    break;
                }
            }
        }
    }


  /*
   * format network data to be sent to Virtualization Agent
   */
  public function networks_VA()
  {

      // fix order of networks
      $server_networks = $this->getEtvaNetworks();
      $networks = array();
      
      foreach($server_networks as $server_network) $networks[] = $server_network->network_VA();
                
      $networks_string = implode(';',$networks);
     
      return $networks_string;
  }

  /*
   * format devices data to be sent to Virtualization Agent
   */
  public function getDevices_VA()
  {
    $devices = $this->getDevices();

    $devices = json_decode($devices);

    if(!$devices) $devices = array();

    $devices_to_send = array();

    foreach($devices as $key=>$d){
        if($d->type == 'usb'){
            $devices_to_send[] = 'vendor='.$d->idvendor.',product='.$d->idproduct.',type='.$d->type; //.',description='.$d->description;
        }elseif($d->type == 'pci'){
            $devices_to_send[] = 'bus='.$d->bus.',slot='.$d->slot.',function='.$d->function.',type='.$d->type; 
        }
    }
    $devices_str = implode(';', $devices_to_send);
    
    return $devices_str;
  }

  /*
   * format disk data to be sent to Virtualization Agent
   */
  public function disks_VA()
  {

      // fix order of disks
      $server_disks = $this->getEtvaServerLogicals();
      if(!$server_disks) $server_disks = array();
      $disks = array();

      foreach($server_disks as $server_disk){          
          $disks[] = $server_disk->serverdisk_VA();
      }

      // add extra cdrom
      $cdromextra = $this->getCdromextra();
      if( isset($cdromextra) && ($this->getVmType() != 'pv') ){
          $cdromextra_str = 'device=cdrom,readonly=1';
          if( $cdromextra ) $cdromextra_str .= strtr(',path=%path%', array('%path%' => $cdromextra));

          $disks[] = $cdromextra_str;
      }

      $disks_string = implode(';',$disks);

      return $disks_string;
  }

  /*
   * returns server start representation for sending to VA
   */
  public function vmMigrate_VA($values)
  {
      $data = array();

  }

  /*
   * returns server representation for sending to VA
   */
  public function _VA()
  {
      $networks = $this->networks_VA();
      $disks = $this->disks_VA();
      $devices = $this->getDevices_VA();



    //  $etva_lv = $this->getEtvaLogicalvolume();
     // $server_path = $etva_lv->getLvdevice();


      $features = (array)json_decode($this->getFeatures());

      $server_VA = array('uuid'=>$this->getUuid(),
                         'name'=> $this->getName(),
                         'boot'=> $this->getBoot(),
                         'location'=> $this->getLocation(),
                         'cdrom'=> $this->getCdrom(),
                         'ram' => $this->getMem(),
                         'vcpu'=>$this->getVcpu(),
                         'sockets'=>$this->getCpuSockets(),
                         'cores'=>$this->getCpuCores(),
                         'threads'=>$this->getCpuThreads(),
                         'state'=> $this->getState(),
                         'vm_state'=> $this->getVmState(),
                         'network'=>$networks,
                         'disk'=>$disks,
                         'vm_os'=> $this->getVmOs(),
                         'vnc_port'=>$this->getVncPort(),
                         'vm_type' => $this->getVmType(),
                         'vnc_listen'=>'any',
                         'vnc_keymap'=>$this->getVncKeymap(),
                         'autostart'=>$this->getAutostart(),
                         'hostdevs'=>$devices,
                         'priority_ha'=>$this->getPriorityHa(),
                         'hasHA'=>$this->getHasHa(),
                         'features'=>$features
      );
      //if( $devices ) $server_VA['no_tablet'] = true;




      return $server_VA;

  }


  public function getEtvaLogicalvolumes(Criteria $criteria = null)
  {
      if(!$criteria) $criteria = new Criteria();

      $criteria->add(EtvaServerLogicalPeer::SERVER_ID, $this->getId());
      $criteria->addJoin(EtvaLogicalvolumePeer::ID, EtvaServerLogicalPeer::LOGICALVOLUME_ID);
      return EtvaLogicalvolumePeer::doSelect($criteria);
  }

  public function getEtvaServerLogical(Criteria $criteria = null)
  {
      if(!$criteria) $criteria = new Criteria();

      return EtvaServerLogicalQuery::create(null,$criteria)
                 ->useEtvaServerQuery()
                     ->filterById($this->getId())
                 ->endUse()
                 ->joinWith('EtvaLogicalvolume')
                 ->find();

  }

  /*
   * checks if all disks of server are not local
   */
  public function isAllSharedLogicalvolumes()
  {
      $lvs = $this->getEtvaLogicalvolumes();
      $all_shared = true;

      foreach($lvs as $lv){
          if($lv->getStorageType() == EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP){
              $all_shared = false;
              break;
          }
      }
      

      return $all_shared;      
  }


  /*
   * checks if has at least one shared lv
   */
  public function hasSharedLogicalvolume()
  {
      $lvs = $this->getEtvaLogicalvolumes();
      $has_shared = false;

      foreach($lvs as $lv){
          if($lv->getStorageType() != EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP){
              $has_shared = true;
              break;
          }
      }


      return $has_shared;
  }

  /*
   * checks if at least one lv has snapshots
   */
  public function hasLogicalvolumeSnapshots()
  {
      $lvs = $this->getEtvaLogicalvolumes();
      $has_snapshots = false;

      foreach($lvs as $lv){
          if($lv->getEtvaLogicalVolumesSnapshots()){
              $has_snapshots = true;
              break;
          }
      }
      return $has_snapshots;
  }
  /*
   * check if server has snapshots (libvirt) or if has disk with lv snapshots (lvm)
   */
  public function hasSnapshots(){
      $has_snapshots = false;
      if( $this->getHasSnapshots() ){    // check if has snapshots flag
          $has_snapshots = true;
      } else {
          $has_snapshots = $this->hasLogicalvolumeSnapshots();
      }
      return $has_snapshots;
  }
  /*
   * check if can do snapshots in this server
   */
  public function hasSnapshotsSupport(){
      $server_lvs = EtvaLogicalvolumeQuery::create()
                ->useEtvaServerLogicalQuery()
                    ->filterByServerId($this->getId())
                ->endUse()
                ->find();
      foreach( $server_lvs as $lv ){
          if( $lv->getFormat() != 'qcow2' ){
              return false;
          }
      }
      return true;
  }

       
  /*
   * Delete server with optionnal keep filesystem flag
   */
  public function deleteServer($keep_fs = true)
  {
      $server_node = $this->getEtvaNode();
      $con = Propel::getConnection(EtvaServerPeer::DATABASE_NAME, Propel::CONNECTION_WRITE);
      //$con->setAttribute(PDO::ATTR_TIMEOUT,10);

      try {
			$con->beginTransaction();
           
            $this->deleteNetworks($con);

            $this->deleteRRAFiles();

            $this->deleteDisks($keep_fs,$con);

            EtvaServerPeer::doDelete($this, $con);

            
            $server_node->updateMemFree();
            $server_node->save();            

            $con->commit();


		} catch (PropelException $e) {
			$con->rollBack();
			throw $e;
		}

  }

  public function deleteServices()
  {
      $c = new Criteria();
      $c->add(EtvaServicePeer::SERVER_ID, $this->getId());
      EtvaServicePeer::doDelete($c);

  }

  public function retrieveByMac($mac){

      $criteria = new Criteria();
      $criteria->add(EtvaNetworkPeer::SERVER_ID,$this->getId());

      $etva_network = EtvaNetworkPeer::retrieveByMac($mac,$criteria);
      return $etva_network;
  }
  
  
  public function deleteNetworks(PropelPDO $con = null)
  {
      $etva_networks = $this->getEtvaNetworks();
      foreach($etva_networks as $etva_network){
                $etva_network->delete($con);
      }

  }

  public function deleteDisks($keep_fs = true, PropelPDO $con = null)
  {
      $server_logicalvols = $this->getEtvaServerLogicals();

      foreach ($server_logicalvols as $server_logicalvol){

          $logicalvol = $server_logicalvol->getEtvaLogicalvolume();

          if($keep_fs){
            
              $logicalvol->setInUse(0);          
              $logicalvol->save($con);
          }else{             
              $logicalvol->delete($con);
          }          
          $server_logicalvol->delete($con);          
      }      

  }

  public function deleteNetworkByMac($mac)
  {

      $criteria = new Criteria();
      $criteria->add(EtvaNetworkPeer::SERVER_ID,$this->getId());      

      $etva_network = EtvaNetworkPeer::retrieveByMac($mac,$criteria);
      $etva_network->delete();

  }


  public function deleteRRAFiles()
  {
      $node = $this->getEtvaNode();
      $node_uuid = $node->getUuid();

      $cpu_per_rra = new ServerCpuUsageRRA($node_uuid, $this->getUuid(),false);
      $cpu_per_rra->delete();

      $server_memory_per_rra = new ServerMemoryUsageRRA($node_uuid, $this->getUuid(),false);
      $server_memory_per_rra->delete();
            
      $server_memory = new ServerMemoryRRA($node_uuid, $this->getUuid(),false);
      $server_memory->delete();
  }

  /*
   * sets first logicalvolume as boot disk
   */
  public function setBootDisk()
  {
      $lv = EtvaServerLogicalPeer::getBootDisk($this->getId());
      $this->setEtvaLogicalvolume($lv);
  }


  public function setSoapTimeout($val) //seconds
  {
      $this->rcv_timeout = $val;
  }


  /*
   * send soap request to management agent
   */
  public function soapSend($method,$params=null)
  {
      
        $dispatcher = sfContext::getInstance()->getEventDispatcher();

        $addr = $this->getIP();
        $port = $this->getAgentPort();
        $proto = "tcp";
        $host = "" . $proto . "://" . $addr . ":" . $port;
        $agent = $this->getName();

        if(!$params) $params = array("nil"=>"true");

        $request_array = array('request'=>array(
                            'agent'=>$agent,
                            'host'=>$host,
                            'port'=>$port,
                            'method'=>$method,
                            'params'=>$params));

        $soap = new soapClient_($host,$port);
        if($this->rcv_timeout) $soap->set_rcv_timeout($this->rcv_timeout);
        $response = $soap->processSoap($method, $params);
        $response['agent'] = $this->getName();

        $response_array = array('response'=>$response);

        $all_params = array_merge($request_array,$response_array);

        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));
        
        return $response;

    }

    /*
     * gets array of fields names in DB
     */
    public function toDisplay()
    {

        $array_data = $this->toArray(BasePeer::TYPE_FIELDNAME);
        return $array_data;

    }


    /*
     * if vm state is not running MA cannot be running
     */
    public function setVmState($v)
	{
        if($v!='running') $this->setState(0);
        return parent::setVmState($v);
	} 
  
    /*
     * getEtvaNetworks: redefined parent method for correct order (EtvaNetworkPeer::PORT)
     */
	public function getEtvaNetworks($criteria = null, PropelPDO $con = null)
	{
		if(null === $this->collEtvaNetworks || null !== $criteria) {
            if( null === $criteria ){
              $criteria = new Criteria();
              $criteria->addAscendingOrderByColumn(EtvaNetworkPeer::PORT);
            }
            $this->collEtvaNetworks = parent::getEtvaNetworks($criteria,$con);
        }
		return $this->collEtvaNetworks;
	}

    /*
     * getEtvaServerLogicals: redefined parent method for correct order (EtvaServerLogicalPeer::BOOT_DISK)
     */
	public function getEtvaServerLogicals($criteria = null, PropelPDO $con = null)
	{
		if(null === $this->collEtvaServerLogicals || null !== $criteria) {
            if(null === $criteria) {
                $criteria = new Criteria();
                $criteria->addAscendingOrderByColumn(EtvaServerLogicalPeer::BOOT_DISK);
            }
            $this->collEtvaServerLogicals = parent::getEtvaServerLogicals($criteria,$con);
		}
		return $this->collEtvaServerLogicals;
	}
    public function hasEtvaServerLogicals(){
        $count = EtvaServerLogicalQuery::create()
                        ->filterByServerId($this->getId())
                        ->count();
        return $count ? true : false;
    }
    public function hasDevices(){
        $devs = $this->getDevices();
        if( $devs ){
            $devices_arr = json_decode($devs);
            if(isset($devices_arr) && count($devices_arr)>0 ){
                return true;
            }
        }
        return false;
    }

    public function canMove(){
        $hasdevs = $this->hasDevices();
        return ($hasdevs) ? False: True;
    }

    public function canMigrate(){
        return $this->canMove();
    }

    public function mergeGaInfo($json){
        $new_gainfo = json_decode($json);
        if( $name = $new_gainfo->__name__ ){
            $old_json = $this->getGaInfo();
            $up_gainfo = (array)json_decode($old_json);
            $up_gainfo[$name] = $new_gainfo;
            $new_json = json_encode($up_gainfo);
            $this->setGaInfo($new_json);
        }
    }
    public function resetHeartbeat($state=EtvaServerPeer::_GA_RUNNING_){
        $ts = time();   // always use my ts
        $this->setHeartbeat($ts);    // reset heart beat
        $this->setGaState($state);
    }
    public function getUnassigned(){
        $count = EtvaServerAssignQuery::create()
                        ->filterByServerId($this->getId())
                        ->count();
        return ($count) ? false : true;
    }
    public function setUnassigned(){
        EtvaServerAssignQuery::create()
                        ->filterByServerId($this->getId())
                        ->delete();
    }
    public function getEtvaNode(){
        return EtvaNodeQuery::create()
                        ->useEtvaServerAssignQuery('ServerAssign','INNER JOIN')
                            ->filterByServerId($this->getId())
                        ->endUse()
                        ->findOne();
    }
    public function assignTo(EtvaNode $etva_node){
        EtvaServerAssignQuery::create()
                        ->filterByServerId($this->getId())
                        ->filterByNodeId($etva_node->getId(),Criteria::NOT_EQUAL)
                        ->delete();
        $serverassign = new EtvaServerAssign();
        $serverassign->setServerId($this->getId());
        $serverassign->setNodeId($etva_node->getId());
        $serverassign->save();
    }
    public function listNodesAssignTo($migrate = false){

        $nodes_assign = array();
        $actual_node = $this->getEtvaNode();
        if( !$migrate && $actual_node ){
            array_push($nodes_assign,$actual_node);
        } else {
            $nodes =
                EtvaNodeQuery::create()
                            ->leftJoin('EtvaNode.EtvaServerAssign')
                            ->leftJoin('EtvaServerAssign.EtvaServer')
                            ->addJoinCondition('EtvaServer','EtvaServer.VmState = ?',EtvaServer::RUNNING)
                            ->withColumn('sum(EtvaServer.Vcpu)','sum_vcpu')
                            ->withColumn('sum(EtvaServer.Vcpu)/EtvaNode.Cputotal','per_cpu')
                            ->withColumn('((sum(EtvaServer.Vcpu)/EtvaNode.Cputotal)+(1-(EtvaNode.Memfree/EtvaNode.Memtotal)))/2','per_res')
                            ->withColumn('1-(EtvaNode.Memfree/EtvaNode.Memtotal)','per_mem')
                            ->groupBy('EtvaNode.Id')
                            ->orderBy('per_res','asc')
                            ->orderBy('per_mem','asc')
                            ->orderBy('per_cpu','asc')
                            ->filterByClusterId($this->getClusterId())
                            ->filterByState(EtvaNode::NODE_ACTIVE)
                            ->find();
            if( count($nodes) == 1 ||  ((count($nodes)>1) && $this->isAllSharedLogicalvolumes()) ){
                # TODO calc assign gate
                foreach($nodes as $i => $node){
                    if( !$actual_node || ($actual_node->getId() != $node->getId()) )
                        if( $node->canAssignServer($this) ) array_push($nodes_assign,$node);
                }
            } else {
                $nodes_withdisks = 
                                EtvaNodeQuery::create()
                                            ->leftJoin('EtvaNode.EtvaServerAssign')
                                            ->leftJoin('EtvaServerAssign.EtvaServer')
                                            ->addJoinCondition('EtvaServer','EtvaServer.VmState = ?',EtvaServer::RUNNING)
                                            ->withColumn('sum(EtvaServer.Vcpu)','sum_vcpu')
                                            ->withColumn('sum(EtvaServer.Vcpu)/EtvaNode.Cputotal','per_cpu')
                                            ->withColumn('((sum(EtvaServer.Vcpu)/EtvaNode.Cputotal)+(1-(EtvaNode.Memfree/EtvaNode.Memtotal)))/2','per_res')
                                            ->withColumn('1-(EtvaNode.Memfree/EtvaNode.Memtotal)','per_mem')
                                            ->groupBy('EtvaNode.Id')
                                            ->orderBy('per_res','asc')
                                            ->orderBy('per_mem','asc')
                                            ->orderBy('per_cpu','asc')
                                            ->filterByClusterId($this->getClusterId())
                                            ->filterByState(EtvaNode::NODE_ACTIVE)
                                            ->useEtvaNodeLogicalvolumeQuery('NodeLogicalvolume','INNER JOIN')
                                                ->useEtvaLogicalvolumeQuery('Logicalvolume','INNER JOIN')
                                                    ->useEtvaServerLogicalQuery('ServerLogical','INNER JOIN')
                                                        ->filterByServerId($this->getId())
                                                    ->endUse()
                                                ->endUse()
                                            ->endUse()
                                            ->find();
                foreach($nodes_withdisks as $i => $node){
                    if( !$actual_node || ($actual_node->getId() != $node->getId()) )
                        if( $node->canAssignServer($this) ) array_push($nodes_assign,$node);
                }
            }
        }
        //usort($nodes_assign,'cmp_nodes_by_resources');
        return $nodes_assign;
    }
    public function canAssignTo(EtvaNode $etva_node){
        $nodes_toassign = $this->listNodesAssignTo();
        foreach($nodes_toassign as $i => $node){
            if( $etva_node->getId() == $node->getId() ){
                return true;
            }
        }
        return false;
    }
}
